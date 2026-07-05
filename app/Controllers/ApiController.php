<?php
namespace App\Controllers;

use \App\Services\{Router, Auth, DB, Json};
use \App\Controllers\ExceptionRegister;
use \App\Core\Page;
use \App\Controllers\Api\{Login, Register, OpenVKAuth};
use \App\Controllers\Api\Subscribe as SubscribeUser;
use \App\Controllers\Api\Images\{Upload, Update as PhotoUpdate};
use \App\Controllers\Api\Images\Rate as PhotoVote;
use \App\Controllers\Api\Images\Compress as PhotoCompress;
use \App\Controllers\Api\Images\CheckAll as PhotoCheckAll;
use \App\Controllers\Api\Images\LoadRecent as PhotoLoadRecent;
use \App\Controllers\Api\Images\LoadNew as PhotoLoadNew;
use \App\Controllers\Api\Images\LoadRandom as PhotoLoadRandom;
use \App\Controllers\Api\LegacyApi;
use \App\Controllers\Api\Images\Move as PhotoMove;
use \App\Controllers\Api\Images\LoadMap as PhotoLoadMap;
use \App\Controllers\Api\Images\Favorite as PhotoFavorite;
use \App\Controllers\Api\Images\Stats as PhotoStats;
use \App\Controllers\Api\Images\Comments\Create as PhotoComment;
use \App\Controllers\Api\Images\Comments\Edit as PhotoCommentEdit;
use \App\Controllers\Api\Images\Comments\Delete as PhotoCommentDelete;
use \App\Controllers\Api\Images\Comments\Pin as PhotoCommentPin;
use \App\Controllers\Api\Images\Comments\Load as PhotoCommentLoad;
use \App\Controllers\Api\Images\Comments\Rate as PhotoCommentVote;
use \App\Controllers\Api\Images\Contests\SendPretend as PhotoContestsSendPretend;
use \App\Controllers\Api\Images\Contests\Rate as PhotoContestsRate;
use \App\Controllers\Api\Contests\GetInfo as ContestsGetInfo;
use \App\Controllers\Api\GeoDB\Search as GeoDBSearch;
use \App\Controllers\Api\Vehicles\Load as VehiclesLoad;
use \App\Controllers\Api\Profile\Update as ProfileUpdate;
use \App\Controllers\Api\Users\LoadUser as UserLoad;
use \App\Controllers\Api\Users\EmailVerify as EmailVerify;
use \App\Controllers\Api\Users\Search as UsersSearch;
use \App\Controllers\Api\Admin\Images\SetVisibility as AdminPhotoSetVisibility;
use \App\Controllers\Api\Admin\News\Create as AdminCreateNews;
use \App\Controllers\Api\Admin\News\Get as AdminGetNews;
use \App\Controllers\Api\Admin\News\Update as AdminUpdateNews;
use \App\Controllers\Api\Admin\News\Load as AdminLoadNews;
use \App\Controllers\Api\Admin\News\Delete as AdminDeleteNews;
use \App\Controllers\Api\Admin\Pages\Create as AdminCreatePage;
use \App\Controllers\Api\Admin\Pages\Get as AdminGetPage;
use \App\Controllers\Api\Admin\Pages\Update as AdminUpdatePage;
use \App\Controllers\Api\Admin\Pages\Load as AdminLoadPages;
use \App\Controllers\Api\Admin\Pages\Delete as AdminDeletePage;
use \App\Controllers\Api\Admin\Chronology\Create as AdminCreateChronology;
use \App\Controllers\Api\Admin\Chronology\Delete as AdminDeleteChronology;
use \App\Controllers\Api\Admin\Links\Create as AdminCreateLink;
use \App\Controllers\Api\Admin\Links\Delete as AdminDeleteLink;
use \App\Controllers\Api\Admin\Radio\Create as AdminCreateRadio;
use \App\Controllers\Api\Admin\Radio\Delete as AdminDeleteRadio;
use \App\Controllers\Api\Admin\GetVehicleInputs as AdminGetVehicleInputs;
use \App\Controllers\Api\Admin\Models\RequestHandler as AdminModelsRequestHandler;
use \App\Controllers\Api\Admin\GeoDB\Create as AdminGeoDBCreate;
use \App\Controllers\Api\Admin\GeoDB\Load as AdminGeoDBLoad;
use \App\Controllers\Api\Admin\GeoDB\Delete as AdminGeoDBDelete;
use \App\Controllers\Api\Admin\Contests\CreateTheme as AdminContestsCreateTheme;
use \App\Controllers\Api\Admin\Contests\Create as AdminContestsCreate;
use \App\Controllers\Api\Admin\Contests\ForceClose as AdminContestsForceClose;
use \App\Controllers\Api\Admin\Contests\Cancel as AdminContestsCancel;
use \App\Controllers\Api\Admin\Settings\TaskManager as AdminTaskManager;
use \App\Controllers\Api\Admin\Settings\Auth as AdminSettingsAuth;
use \App\Controllers\Api\Admin\Settings\Music as AdminSettingsMusic;
use \App\Controllers\Api\Admin\Settings\AuthProvider as AdminSettingsAuthProvider;
use \App\Controllers\Api\Admin\Settings\Debug as AdminSettingsDebug;
use \App\Controllers\Api\Admin\Settings\ServerConfig as AdminSettingsServerConfig;
use \App\Controllers\Api\Admin\Users\Update as AdminUserUpdate;
use \App\Controllers\Api\Messages\GetChats as MSGGetChats;
use \App\Controllers\Api\Messages\UploadFile as MSGUpload;
use \App\Controllers\Api\Messages\GetUsers as MSGGetUsers;
use \App\Controllers\Api\Messages\CreateChat as MSGCreateChat;
use \App\Controllers\Api\Emoji\Load as EmojiLoad;
use \App\Controllers\Api\Audio\Upload as AudioUpload;
use \App\Controllers\Api\Audio\CreateStream as AudioCreateStream;
use \App\Controllers\Api\Audio\AddUrl as AudioAddUrl;
use \App\Controllers\Api\Audio\Library as AudioLibraryApi;
use \App\Controllers\Api\Audio\Delete as AudioDelete;
use \App\Controllers\Api\Audio\Playlist as AudioPlaylist;
use \App\Controllers\Api\Audio\Proxy as AudioProxy;
use \App\Controllers\Api\Audio\Metadata as AudioMetadata;

class ApiController
{

  
    public static function login() {
        return new Login();
    }
    public static function register() {
        return new Register();
    }
    public static function openvkauth() {
        return new OpenVKAuth();
    }
    public static function upload() {
        return new Upload();
    }
    public static function photoedit() {
        return new PhotoUpdate();
    }
    public static function emailverify() {
        return new EmailVerify();
    }
    public static function photovote() {
        return new PhotoVote();
    }
    public static function photovotecontest() {
        return new PhotoContestsRate();
    }
    public static function photofavorite() {
        return new PhotoFavorite();
    }
    public static function photocomment() {
        return new PhotoComment();
    }
    public static function photocommentedit() {
        return new PhotoCommentEdit();
    }
    public static function photocommentdelete() {
        return new PhotoCommentDelete();
    }
    public static function photocommentpin() {
        return new PhotoCommentPin();
    }
    public static function photocommentvote() {
        return new PhotoCommentVote();
    }
    public static function photocommentload() {
        return new PhotoCommentLoad();
    }
    public static function updateprofile() {
        return new ProfileUpdate();
    }
    public static function photocompress() {
        return new PhotoCompress();
    }
    public static function geodbsearch() {
        return new GeoDBSearch();
    }
    public static function adminsetvis() {
        return new AdminPhotoSetVisibility();
    }
    public static function subscribeuser() {
        return new SubscribeUser();
    }
    public static function checkallphotos() {
        return new PhotoCheckAll();
    }
    public static function recentphotos() {
        return new PhotoLoadRecent();
    }
    public static function loadnewphotos() {
        return new PhotoLoadNew();
    }
    public static function legacyapi() {
        return new LegacyApi();
    }
    public static function randomphotos() {
        return new PhotoLoadRandom();
    }
    public static function photomove() {
        return new PhotoMove();
    }
    public static function sendpretendphoto() {
        return new PhotoContestsSendPretend();
    }
    public static function loaduser() {
        return new UserLoad();
    }
    public static function photostats() {
        return new PhotoStats();
    }
    public static function admincreatenews() {
        return new AdminCreateNews();
    }
    public static function admingetnews() {
        return new AdminGetNews();
    }
    public static function admineditnews() {
        return new AdminUpdateNews();
    }
    public static function admindeletenews() {
        return new AdminDeleteNews();
    }
    public static function adminloadnews() {
        return new AdminLoadNews();
    }
    public static function admincreatepage() {
        return new AdminCreatePage();
    }
    public static function admingetpage() {
        return new AdminGetPage();
    }
    public static function admineditpage() {
        return new AdminUpdatePage();
    }
    public static function adminloadpages() {
        return new AdminLoadPages();
    }
    public static function admindeletepage() {
        return new AdminDeletePage();
    }
    public static function admincreatechronology() {
        return new AdminCreateChronology();
    }
    public static function admindeletechronology() {
        return new AdminDeleteChronology();
    }
    public static function admincreatelink() {
        return new AdminCreateLink();
    }
    public static function admindeletelink() {
        return new AdminDeleteLink();
    }
    public static function admincreateradio() {
        return new AdminCreateRadio();
    }
    public static function admindeleteradio() {
        return new AdminDeleteRadio();
    }
    public static function admingetvehicleinputs() {
        return new AdminGetVehicleInputs();
    }
    public static function admincontestscreatetheme() {
        return new AdminContestsCreateTheme();
    }
    public static function admincontestscreate() {
        return new AdminContestsCreate();
    }
    public static function admincontestsforceclose() {
        return new AdminContestsForceClose();
    }
    public static function admincontestscancel() {
        return new AdminContestsCancel();
    }
    public static function admingeodbcreate() {
        return new AdminGeoDBCreate();
    }
    public static function admingeodbload() {
        return new AdminGeoDBLoad();
    }
    public static function admingeodbdelete() {
        return new AdminGeoDBDelete();
    }
    public static function admintaskmanager() {
        return new AdminTaskManager();
    }
    public static function adminsettingsmusic() {
        return new AdminSettingsMusic();
    }
    public static function adminsettingsauth() {
        return new AdminSettingsAuth();
    }
    public static function adminsettingsauthprovider() {
        return new AdminSettingsAuthProvider();
    }
    public static function adminsettingsdebug() {
        return new AdminSettingsDebug();
    }
    public static function adminsettingsserver() {
        return new AdminSettingsServerConfig();
    }
    public static function adminuseredit() {
        return new AdminUserUpdate();
    }
    public static function vehiclesload() {
        return new VehiclesLoad();
    }
    public static function contestsgetinfo() {
        return new ContestsGetInfo();
    }
    public static function msggetchats() {
        return new MSGGetChats();
    }
    public static function msgupload() {
        return new MSGUpload();
    }
    public static function msggetusers() {
        return new MSGGetUsers();
    }
    public static function msgcreatechat() {
        return new MSGCreateChat();
    }
    public static function userssearch() {
        return new UsersSearch();
    }
    public static function emojiload() {
        return new EmojiLoad();
    }
    public static function photoloadmap() {
        return new PhotoLoadMap();
    }
    public static function adminmodelsrequesthandler() {
        return new AdminModelsRequestHandler();
    }

    public static function audioupload() {
        return new AudioUpload();
    }
    public static function audiostream() {
        return new AudioCreateStream();
    }
    public static function audiourl() {
        return new AudioAddUrl();
    }
    public static function audiolibrary() {
        return new AudioLibraryApi();
    }
    public static function audiodelete() {
        return new AudioDelete();
    }
    public static function audioplaylist() {
        return new AudioPlaylist();
    }
    public static function audioproxy() {
        return new AudioProxy();
    }
    public static function audiometadata() {
        return new AudioMetadata();
    }


}