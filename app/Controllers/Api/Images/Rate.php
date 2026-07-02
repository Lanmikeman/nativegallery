<?php

namespace App\Controllers\Api\Images;

use App\Services\{Auth, DB};
use App\Models\{User, Vote, VoteContest};

class Rate
{
    private static function togglePhotoRate(int $userId, int $photoId, int $voteType, int $contestFlag): void
    {
        $current = $contestFlag === 1
            ? Vote::photoContest($userId, $photoId)
            : Vote::photo($userId, $photoId);

        if ($current === -1) {
            DB::query(
                'INSERT INTO photos_rates (id, user_id, photo_id, type, contest) VALUES (NULL, :id, :pid, :type, :contest)',
                [':id' => $userId, ':pid' => $photoId, ':type' => $voteType, ':contest' => $contestFlag]
            );
            $newVote = $contestFlag === 1
                ? Vote::photoContest($userId, $photoId)
                : Vote::photo($userId, $photoId);
            if ($newVote != $voteType) {
                DB::query(
                    'DELETE FROM photos_rates WHERE user_id=:id AND photo_id=:pid AND type=:type AND contest=:contest',
                    [':id' => $userId, ':pid' => $photoId, ':type' => $newVote, ':contest' => $contestFlag]
                );
            }
        } elseif ($current === $voteType) {
            DB::query(
                'DELETE FROM photos_rates WHERE user_id=:id AND photo_id=:pid AND contest=:contest',
                [':id' => $userId, ':pid' => $photoId, ':contest' => $contestFlag]
            );
        } else {
            DB::query(
                'UPDATE photos_rates SET type=:type WHERE user_id=:id AND photo_id=:pid AND contest=:contest',
                [':id' => $userId, ':pid' => $photoId, ':type' => $voteType, ':contest' => $contestFlag]
            );
        }
    }

    private static function toggleContestRate(int $userId, int $photoId, int $voteType, int $contestId): void
    {
        if (VoteContest::photo($userId, $photoId, $contestId) === -1) {
            DB::query(
                'INSERT INTO photos_rates_contest (id, user_id, photo_id, type, contest_id) VALUES (NULL, :id, :pid, :type, :cid)',
                [':id' => $userId, ':pid' => $photoId, ':type' => $voteType, ':cid' => $contestId]
            );
            if (VoteContest::photo($userId, $photoId, $contestId) != $voteType) {
                DB::query(
                    'DELETE FROM photos_rates_contest WHERE user_id=:id AND photo_id=:pid AND type=:type AND contest_id=:cid',
                    [':id' => $userId, ':pid' => $photoId, ':type' => VoteContest::photo($userId, $photoId, $contestId), ':cid' => $contestId]
                );
            }
        } elseif (VoteContest::photo($userId, $photoId, $contestId) === $voteType) {
            DB::query(
                'DELETE FROM photos_rates_contest WHERE user_id=:id AND photo_id=:pid AND contest_id=:cid',
                [':id' => $userId, ':pid' => $photoId, ':cid' => $contestId]
            );
        } else {
            DB::query(
                'UPDATE photos_rates_contest SET type=:type WHERE user_id=:id AND photo_id=:pid AND contest_id=:cid',
                [':id' => $userId, ':pid' => $photoId, ':type' => $voteType, ':cid' => $contestId]
            );
        }
    }

    private static function resolveMode(string $action, $contestId): string
    {
        if ($action === 'vote-photo') {
            return 'photo';
        }

        if ($action === 'vote-konk' || $action === 'vote-author') {
            if ($contestId !== null && $contestId !== '' && (int) $contestId > 0) {
                return 'contest';
            }
            return 'photo_contest_quality';
        }

        return 'photo';
    }

    public function __construct()
    {
        if (!isset($_GET['vote'], $_GET['pid'])) {
            return;
        }

        $userId = Auth::userid();
        $photoId = (int) $_GET['pid'];
        $voteType = (int) $_GET['vote'];
        $action = $_GET['action'] ?? 'vote-photo';
        $contestId = $_GET['cid'] ?? null;
        $mode = self::resolveMode($action, $contestId);

        switch ($mode) {
            case 'contest':
                self::toggleContestRate($userId, $photoId, $voteType, (int) $contestId);
                break;
            case 'photo_contest_quality':
                self::togglePhotoRate($userId, $photoId, $voteType, 1);
                break;
            default:
                self::togglePhotoRate($userId, $photoId, $voteType, 0);
                break;
        }

        $votes = DB::query(
            'SELECT * FROM photos_rates WHERE photo_id=:id AND contest=0 ORDER BY id DESC',
            [':id' => $photoId]
        );
        $formattedVotesPos = [];
        $formattedVotesNeg = [];

        foreach ($votes as $vote) {
            $user = new User($vote['user_id']);
            if ((int) $vote['type'] === 0) {
                $formattedVotesNeg[] = [$vote['user_id'], $user->i('username'), 0];
            } elseif ((int) $vote['type'] === 1) {
                $formattedVotesPos[] = [$vote['user_id'], $user->i('username'), 1];
            }
        }

        $currentVote = Vote::photo($userId, $photoId);
        $contestQualityVote = Vote::photoContest($userId, $photoId);
        $contestVote = ($mode === 'contest')
            ? VoteContest::photo($userId, $photoId, (int) $contestId)
            : -1;

        if ($mode === 'contest') {
            $count = VoteContest::count($photoId, (int) $contestId);
        } else {
            $count = Vote::count($photoId);
        }

        $result = [
            'buttons' => [
                'negbtn' => $currentVote === 0,
                'posbtn' => $currentVote === 1,
                'negbtn_contest' => $mode === 'contest' ? $contestVote === 0 : $contestQualityVote === 0,
                'posbtn_contest' => $mode === 'contest' ? $contestVote === 1 : $contestQualityVote === 1,
            ],
            'errors' => '',
            'rating' => $count,
            'votes' => [
                1 => $formattedVotesPos,
                0 => $formattedVotesNeg,
            ],
        ];

        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}