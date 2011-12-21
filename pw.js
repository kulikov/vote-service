$(document).ready(function() {
  $.ajax({
      url: 'http://vote-service.kulikovd.ru/',
      data: { 'get': 1, 'pwuid': Pw._getUid() },
      dataType: 'script',
      scriptCharset: 'utf-8'
  });
});

Pw = 
{
    vote: function(button)
    {
        if (!$('#pw-vote-form').size()) {
            $('body').append('<div id="pw-vote-form"><div><a href="#" class="pw-close" onclick="$(\'#pw-vote-form\').hide(); return false;">&times;</a><label>Введите ваш email:<input class="pw-inp" value="'+ (Pw._getCookie('pwemail') || '') +'" type="text"/></label><input class="pw-btn" value="Голосовать" onclick="Pw.sendVote($(this).attr(\'img\'), $(this).prev().find(\'input\').val()); " type="button"></div></div>');
        }
        
        $('#pw-vote-form').show();
        $('#pw-vote-form :button').attr('img', $(button).prev().attr('id'));
    },
    
    sendVote: function(imageId, email)
    {
        Pw._setCookie('pwemail', email);
        
        $.ajax({
            url: 'http://vote-service.kulikovd.ru/',
            data: { 'id': imageId, 'pwuid': Pw._getUid(), 'email': email },
            dataType: 'script',
            scriptCharset: 'utf-8',
            success: function() {
                window.location.reload();
            }
        });
    },

    _getUid: function()
    {
        var _uid = Pw._getCookie('pwiud');

        if (!_uid) {
            _uid = 'v_' + (new Date().getTime()) + parseInt(Math.random() * 1000000);
            Pw._setCookie('pwiud', _uid);
        }

        return _uid;
    },
  
    showStat: function(result, voited)
    {
        $.each(result, function(i, e) {
            $('#' + i).append('<div class="pw-result" title="Рейтинг рисунка">'+ e +'</div>');
        });
  
        $.each(voited, function(i, e) {
            $('#' + i).next().remove();
            $('#' + i).after('<div class="pw-already">Ваш голос принят!</div>');
        });
    },
  
    _setCookie: function(name, value) 
    {
        var valueEscaped = escape(value);
        var expiresDate = new Date();
        expiresDate.setTime(expiresDate.getTime() + 365 * 24 * 60 * 60 * 1000); // срок - 1 год, но его можно изменить
        var expires = expiresDate.toGMTString();
        var newCookie = name + "=" + valueEscaped + "; path=/; expires=" + expires;
        if (valueEscaped.length <= 4000) document.cookie = newCookie + ";";
    },
    
    _getCookie: function(name) 
    {
        var prefix = name + "=";
        var cookieStartIndex = document.cookie.indexOf(prefix);
        if (cookieStartIndex == -1) return null;
        var cookieEndIndex = document.cookie.indexOf(";", cookieStartIndex + prefix.length);
        if (cookieEndIndex == -1) cookieEndIndex = document.cookie.length;
        return unescape(document.cookie.substring(cookieStartIndex + prefix.length, cookieEndIndex));
    }
};