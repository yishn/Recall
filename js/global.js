$(document).ready(function() {
    // Autoresize textareas
    autosize($('textarea'))

    // Confirm deleting
    $('button.delete').on('click', function() {
        var result = confirm('Do you really want to delete this item?')
        if (!result) return false
    })

    // Prepare studying
    if ($('#study').length != 0) {
        var currentIndex = 0
        var correctOnce = Object.keys(new Int8Array($('#study li').length)).map(function() { return false })

        var showCard = function(index) {
            $('#study').removeClass('reveal').removeClass('revealnotes')
            $('#study li').css('display', 'none').eq(index).css('display', 'block')

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
                class: 'reveal',
                type: 'button'
            }).on('click', function() {
                $('#study').addClass('reveal')
                return false
        })).append($('<button/>', {
                text: 'Show Notes',
                class: 'shownotes',
                type: 'button'
            }).on('click', function() {
                $('#study').addClass('revealnotes')
                return false
        })).append($('<button/>', {
                text: 'Show Again',
                class: 'showagain',
                type: 'button'
            }).on('click', function() {
                $('#study input[type="checkbox"]').eq(currentIndex).attr('checked', false)
                nextCard()
                return false
        })).append($('<button/>', {
                text: 'Next Card',
                class: 'nextcard',
                type: 'button'
            }).on('click', function() {
                correctOnce[currentIndex] = true
                nextCard()
                return false
        }))

        showCard(0)
    }

    // Infinite adding
    if ($('#addlist').length != 0) {
        var $template = $('#addlist li:last-child').css('display', 'none')

        $('#addlist + p').prepend($('<button/>', {
            text: 'Add Items',
            type: 'button'
        }).on('click', function() {
            for (var i = 0; i < 5; i++) {
                $template.before($template.clone().css('display', 'block'))
            }

            return false
        }))
    }

    // Add edit links
    if ($('section.back, section.notes').length != 0) {
        $('section.back, section.notes').each(function() {
            if ($(this).children('.tasks').length != 0) return

            $(this).prepend($('<p/>', {
                class: 'tasks'
            }).append($('<a/>', {
                href: '#',
                text: 'Edit'
            }).on('click', function() {
                $(this).parents('section').css('display', 'none')
                    .next('textarea').css('display', 'block').get(0).focus()

                return false
            })))
        })

        $('section.back + textarea[name="back"], section.notes + textarea[name="notes"]').css('display', 'none')
    }
})
