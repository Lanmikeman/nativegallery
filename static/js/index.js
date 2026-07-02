$(document).ready(function()
{
	$('.ix-country > a[href="#"]').on('click', function(e)
	{
		var block = $(this).parent().next('div');
		if (block.is(':hidden'))
		{
			block.slideDown();
			$('.ix-arrow', this).addClass('ix-arrow-expanded');
		}
		else
		{
			block.slideUp();
			$('.ix-arrow', this).removeClass('ix-arrow-expanded');
		}

		return false;
	});


	$(window).on('load resize', function()
	{
		var list = $('#idx-regions-list');
		if (!list.length) return;

		var table = list.closest('table');
		if (!table.length) return;

		var h = table.height() - list.position().top + 40;
		if (h > 0) list.css('height', h + 'px');
	});


	$('#loadmore').on('click', LoadRecentPhotos).click();
	if ($('#newrand').length) {
		$('#newrand').on('click', function (e) {
			e.preventDefault();
			LoadRandomPhotos();
		});
	}

	updateInterval = setInterval(LoadPubPhotos, 60000);


	//$('#cname').citySelector('cid', { defaultLabel: _text['IX_ANY'] });


	$('#type').on('change', function() { $('#type').attr('class', $('#type option:selected').attr('class')); }).change();
	$('#num').on('change keyup', function() { $('#qsearch').prop('disabled', $('#num').val().trim() == ''); }).on('keypress', function(event) { if (event.keyCode == 13) { searchVehicles(); return false; } }).change();
	$('#searchbtn').on('click', searchVehicles);


	/*$('#qcity').citySelector(null, {
		selectCallback: function(item) { window.open('/city/' + item.value + '/'); },
		clearField: true
	});*/


	$(document).on('click', function(e)
	{
		var target = $(e.target);
		if (target.closest('#cars_list').length > 0 ||
			target.closest('#idx-column-menu').length > 0 ||
			target.is('#mobile-menu') ||
			target.is('button')) return;

		$('#cars_list').hide().html('');

		var menu = $('div#idx-column-menu');
		if (menu.is(':visible'))
		{
			menu.hide();
			$('#backgr').hide();
		}

		e.stopPropagation();
	});




	$('#mobile-menu').on('click', function()
	{
		$('#idx-column-menu, #backgr').toggle();
		return false;
	});


	$('.ix-plus, .ix-minus').on('click', function()
	{
		$(this).toggleClass('ix-plus ix-minus');

		var block = $(this).closest('.ix-region');
		$('.ix-hideable-citylist, .ix-hideable-cname', block).toggle();

		return false;
	});
});


function searchVehicles()
{
	$('#cars_list').html('<img src="/static/img/loader_wb.gif" style="display:block; padding:2px" alt="">').show();
	$.get('/api.php', { action: 'index-qsearch', cid: $('#cid').val(), type: $('#type').val(), num: $('#num').val() }, function (r) { $('#cars_list').html(r); });
    return false;
}



function AddPhotoToBlock(block, arr, prepend) {
    const photoId = parseInt(arr.id, 10);
    if (!photoId) return;

    const html = `
    <a href="/photo/${photoId}/" data-no-ajax class="prw-grid-item prw-grid-item--link">
        <span class="prw-wrapper">
            ${arr.place}
            <span>${arr.date}</span>
        </span>
        <span class="prw-animate blur-load"
           style="background-image: url('${arr.photourl_extrasmall}')"
           data-src="${arr.photourl_small}">
            ${arr.ccnt != 0 ? `
            <span class="hdshade">
                <span class="com-icon">${arr.ccnt}</span>
            </span>` : ''}
        </span>
    </a>`;

    block[prepend ? 'prepend' : 'append'](html);

    lazyLoadSingleImage(block.find('.blur-load').last()[0]);
}

// Отдельная функция для загрузки одного изображения
function lazyLoadSingleImage(element) {
    const realSrc = element.dataset.src;
    const tempImg = new Image();
    
    tempImg.src = realSrc;
    tempImg.onload = () => {
        element.style.backgroundImage = `url('${realSrc}')`;
        element.classList.add('loaded');
    };
}

// Обновленный обработчик для всей страницы
function lazyLoadImages(selector = '.blur-load') {
    document.querySelectorAll(selector).forEach(element => {
        if(!element.dataset.loaded) { // Проверка чтобы не дублировать загрузку
            element.dataset.loaded = true;
            lazyLoadSingleImage(element);
        }
    });
}

// Инициализация при загрузке и после динамического добавления
window.addEventListener('load', () => {
    lazyLoadImages();
    // Для динамически добавленных элементов можно вызывать lazyLoadImages() после добавления
});



function LoadRandomPhotos()
{
	var random = $('#random-photos');
	var width = random.is('.mobile') ? 0 : random.width();
	var newrand = $('#newrand');
	var loader = $('#newrand-loader');

	newrand.hide();
	loader.show();

	$.getJSON('/api/photo/random', function(data)
	{
		if (Array.isArray(data) && data.length) {
			random.html('');
			for (var i = 0; i < data.length; i++) AddPhotoToBlock(random, data[i]);
		}

		newrand.show();
		loader.hide();
	})
	.fail(function(jx) {
		newrand.show();
		loader.hide();
		if (jx.responseText != '') console.log(jx.responseText);
	});

	return false;
}



function LoadRecentPhotos()
{
	var recent = $('#recent-photos');
	var lastpid = recent.attr('lastpid');
	var width = recent.width();
	var loadmore = $('#loadmore');
	var hidden = $('.prw-grid-item:hidden', recent);

	if (recent.is('.mobile')) width *= 1.5;

	loadmore.prop('disabled', true).addClass('loader-button').val(' ');

	$.getJSON('/api/photo/loadrecent', { lastpid: lastpid }, function(data)
	{
		if (data)
		{
			if (lastpid == 0) recent.attr('firstpid', data[0].id);
			hidden.show();

			for (var i = 0; i < data.length; i++) AddPhotoToBlock(recent, data[i]);
			recent.attr('lastpid', data[i-1].id);
		}
		else recent.append('Load error');

		loadmore.prop('disabled', false).removeClass('loader-button').val('Загрузить ещё');
	})
	.fail(function(jx) { if (jx.responseText != '') console.log(jx.responseText); });
}

function startCountdown(unixTimestamp) {
	function padZero(num) {
		return num < 10 ? '0' + num : num;
	}

	function getWord(num, words) {
		if (num % 10 === 1 && num % 100 !== 11) return words[0];
		if (num % 10 >= 2 && num % 10 <= 4 && (num % 100 < 10 || num % 100 >= 20)) return words[1];
		return words[2];
	}

	function updateTimer() {
		const now = Math.floor(Date.now() / 1000);
		const diff = unixTimestamp - now;

		if (diff <= 0) {
			clearInterval(interval);
			const el = document.getElementById('countdown');
			if (el) el.textContent = "Скоро";
			return;
		}

		const days = Math.floor(diff / 86400);
		const hours = Math.floor((diff % 86400) / 3600);
		const minutes = Math.floor((diff % 3600) / 60);
		const seconds = diff % 60;

		document.getElementById('countdown').textContent =
			`${padZero(days)} ${getWord(days, ['день', 'дня', 'дней'])} ` +
			`${padZero(hours)} ${getWord(hours, ['час', 'часа', 'часов'])} ` +
			`${padZero(minutes)} ${getWord(minutes, ['минута', 'минуты', 'минут'])} ` +
			`${padZero(seconds)} ${getWord(seconds, ['секунда', 'секунды', 'секунд'])}`;
	}

	updateTimer(); // сразу обновляем отображение
	const interval = setInterval(updateTimer, 1000);
}

function LoadPubPhotos()
{
	var recent = $('#recent-photos');
	var firstpid = recent.attr('firstpid');

	if (firstpid == 0) return;

	$.getJSON('/api.php', { action: 'get-pub-photos', firstpid: firstpid }, function(data)
	{
		if (data)
		{
			if (data.length)
			{
				for (var i = 0; i < data.length; i++)
				{
					$('.prw-grid-item:visible', recent).eq(-1).hide();
					AddPhotoToBlock(recent, data[i], true);
				}

				recent.attr('firstpid', data[i-1].pid);
			}
		}
		else clearInterval(updateInterval);
	})
	.fail(function(jx) { if (jx.responseText != '') console.log(jx.responseText); });
}