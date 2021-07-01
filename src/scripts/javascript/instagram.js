// Подгрузка ленты из Инстаграма
if ($('.instagram-feed').attr('hashtag') != undefined) {
  var hashtags = $('.instagram-feed').attr('hashtag').split(' ');
  $.instagramFeed({
    'tag': hashtags[0],
    'get_data': true,
    'callback': function (data) {
      var posts = data['edge_hashtag_to_media']['edges'];
      posts.forEach(function (item, index) {

        var imageUrl = item['node']['thumbnail_resources'][4]['src'];
        var url = 'https://www.instagram.com/p/' + item['node']['shortcode'];
        var text = item['node']['edge_media_to_caption']['edges'][0]['node']['text'];

        var likes = item['node']['edge_liked_by']['count'];

        var regex = /Стоимость работ: ([\d\s]+) руб/gm, match;
        while ((match = regex.exec(text)) !== null) {
            if (match.index === regex.lastIndex) regex.lastIndex++;
            var price = new Intl.NumberFormat('ru-RU').format(Number(match[1].replace(/\s+/g, '')));
        }
        if (price != undefined) { price = '<div class="text"><div class="label">Стоимость данной работы</div><div class="price">' + price + ' ₽</div></div>'; } else price = '';

        var tags = text.substr(text.indexOf('#') + 1).split('#'), matchedTags = 0;
        tags = tags.map(function (item) { return item.trim(); });
        hashtags.forEach(function (item) { if (tags.includes(item)) matchedTags++; });
        if (matchedTags == hashtags.length) {
          $('.instagram-feed').append('<div class="col-4 col-md-3 col-lg-2"><a class="photo" href="' + url + '"><img src="' + imageUrl + '">' + price + '</a></div>');
        }
      });
    }
  });
} else if ($('.instagram-feed').attr('username') != undefined) {
  var username = $('.instagram-feed').attr('username');
  $.instagramFeed({
    'username': username,
    'get_data': true,
    'callback': function (data) {
      var posts = data['edge_owner_to_timeline_media']['edges'];
      posts.forEach(function (item, index) {
        if (index < 12) {
          var imageUrl = item['node']['thumbnail_resources'][4]['src'];
          var url = 'https://www.instagram.com/p/' + item['node']['shortcode'];
          var text = item['node']['edge_media_to_caption']['edges'][0]['node']['text'];

          var likes = item['node']['edge_liked_by']['count'];

          var regex = /Стоимость работ: ([\d\s]+) руб/gm, match;
          while ((match = regex.exec(text)) !== null) {
              if (match.index === regex.lastIndex) regex.lastIndex++;
              var price = new Intl.NumberFormat('ru-RU').format(Number(match[1].replace(/\s+/g, '')));
          }
          if (price != undefined) { price = '<div class="text"><div class="label">Стоимость данной работы</div><div class="price">' + price + ' ₽</div></div>'; } else price = '';

          $('.instagram-feed').append('<div class="col-4 col-md-3 col-lg-2"><a class="photo" href="' + url + '"><img src="' + imageUrl + '">' + price + '</a></div>');
        }
      });
    }
  });
}