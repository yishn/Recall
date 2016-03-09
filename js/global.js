$(document).ready(function() {
    // Autoresize textareas
    autosize($('textarea'))

    // Prepare studying
    if ($('#study').length != 0) {
        var currentIndex = 0
        var correctOnce = Object.keys(new Int8Array($('#study li').length)).map(function() { return false })

        var showCard = function(index) {
            $('#study li').css('display', 'none').eq(index).css('display', 'block')
            $('#study').removeClass('reveal').removeClass('revealnotes')

            currentIndex = index

            var percent = Math.round(correctOnce.filter(function(x) { return x }).length * 100 / correctOnce.length)
            $('#progress span').css('width', percent + '%')
        }

        var nextCard = function() {
            if (correctOnce.every(function(x) { return x })) {
                $('form').get(0).submit()
                return
            }

            var i = (currentIndex + 1) % correctOnce.length

            while (correctOnce[i]) {
                if (i + 1 == correctOnce.length) i = -1
                i++
            }

            showCard(i)
        }

        $('#study input[type="checkbox"]').attr('checked', '')
        $('#study label, button[type="submit"]').css('display', 'none')
        $('header').after($('<div/>', { id: 'progress' }).append($('<span/>')))

        $('#study + p').append($('<button/>', {
                text: 'Reveal',
                class: 'reveal'
            }).on('click', function() {
                $('#study').addClass('reveal')
                return false
        })).append($('<button/>', {
                text: 'Show Notes',
                class: 'shownotes'
            }).on('click', function() {
                $('#study').addClass('revealnotes')
                return false
        })).append($('<button/>', {
                text: 'Show Again',
                class: 'showagain'
            }).on('click', function() {
                $('#study input[type="checkbox"]').eq(currentIndex).attr('checked', false)
                nextCard()
                return false
        })).append($('<button/>', {
                text: 'Next Card',
                class: 'nextcard'
            }).on('click', function() {
                correctOnce[currentIndex] = true
                nextCard()
                return false
        }))

        showCard(0)
    }
})
