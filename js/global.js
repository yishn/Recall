$(document).ready(function() {
    var basepath = $('header h1 a').attr('href')

    // Autoresize textareas
    autosize($('textarea'))

    // Confirm deleting
    $('button.delete').on('click', function() {
        var result = confirm('Do you really want to delete this item?')
        if (!result) return false
    })

    // Prepare studying
    if ($('#study').length != 0) {
        var correctOnce = Object.keys(new Int8Array($('#study > li').length)).map(function() { return false })
        var currentIndex = Math.floor(Math.random() * correctOnce.length)
        var identity = function(x) { return x }

        var showCard = function(index) {
            $('#study').removeClass('reveal').removeClass('revealnotes')
            $('#study > li').css('display', 'none').eq(index).css('display', 'block')

            currentIndex = index

            var percent = Math.round(correctOnce.filter(identity).length * 100 / correctOnce.length)
            $('#progress span').css('width', percent + '%')
        }

        var nextCard = function() {
            var doneCount = correctOnce.filter(identity).length

            if (doneCount == correctOnce.length) {
                $('form').get(0).submit()
                return
            }

            var i = currentIndex

            if (doneCount == correctOnce.length - 1) {
                i = correctOnce.indexOf(false)
            } else {
                while (correctOnce[i] || i == currentIndex) {
                    i = Math.floor(Math.random() * correctOnce.length)
                }
            }

            showCard(i)
        }

        $('#study input[type="checkbox"]').attr('checked', '')
        $('#study label, button[type="submit"]').css('display', 'none')
        $('header').append($('<div/>', { id: 'progress' }).append($('<span/>')))

        $('#study section .tasks a').on('click', function() {
            $('#study').addClass('edit')

            var $textarea = $(this).parents('section').next('textarea')
            autosize.update($textarea)
            $textarea.focus()

            return false
        })

        $('#study + p').append($('<button/>', {
                text: 'Reveal',
                class: 'reveal',
                type: 'button'
            }).on('click', function() {
                $('#study').addClass('reveal')
                $el = $('#study > li section.back').eq(currentIndex)
                $('html, body').animate({ scrollTop: $el.offset().top })
                return false
        })).append($('<button/>', {
                html: '&larr; Show Again',
                class: 'showagain',
                type: 'button'
            }).on('click', function() {
                $('#study input[type="checkbox"]').eq(currentIndex).attr('checked', false)
                nextCard()
                return false
        })).append($('<button/>', {
                text: 'Show Notes',
                class: 'shownotes',
                type: 'button'
            }).on('click', function() {
                $('#study').addClass('revealnotes')
                $el = $('#study > li section.notes').eq(currentIndex)
                $('html, body').animate({ scrollTop: $el.offset().top })
                return false
        })).append($('<button/>', {
                html: 'Next Card &rarr;',
                class: 'nextcard',
                type: 'button'
            }).on('click', function() {
                correctOnce[currentIndex] = true
                nextCard()
                return false
        })).after($('<p/>').append($('<button/>', {
            text: 'Update',
            type: 'button'
        }).on('click', function() {
            var $li = $('#study > li').eq(currentIndex)
            var $button = $(this)
            var id = $li.find('section.back + textarea').attr('name').replace('back-', '')

            $button.attr('disabled', '')

            $.post(basepath + 'edit/' + id, {
                back: $li.find('section.back + textarea').val(),
                notes: $li.find('section.notes + textarea').val(),
            }, function(data) {
                var $result = $(data)

                $li.find('section.back .inner').html($result.find('section.back .inner').html())
                $li.find('section.notes .inner').html($result.find('section.notes .inner').html())

                $button.attr('disabled', false)
                $('#study').removeClass('edit')
            })

            return false
        })))

        // Keyboard shortcuts

        $(document).on('keyup', function(e) {
            if ($('#study').hasClass('edit')) return true

            if (e.keyCode == 13 || e.charCode == 32) {
                // Enter or spacebar

                var $el = null
                $('button').blur()

                if (!$('#study').hasClass('reveal')) {
                    $('button.reveal').click()
                } else {
                    $('button.shownotes').click()
                }
            } else if (e.keyCode == 37) {
                // Left arrow

                if ($('#study').hasClass('reveal')) $('button.showagain').click()
                else $('button.reveal').click()
            } else if (e.keyCode == 39) {
                // Right arrow

                if ($('#study').hasClass('reveal')) $('button.nextcard').click()
                else $('button.reveal').click()
            } else {
                return true
            }

            return false
        })

        showCard(currentIndex)
    }

    // Render progress
    $('.progress').each(function() {
        var $progress = $(this).addClass('render')
        var total = $progress.find('li span').get()
            .map(function(span) { return parseFloat($(span).text()) })
            .reduce(function(x, y) { return x + y }, 0)

        $progress.children('li').each(function() {
            var $li = $(this)
            var percent = parseFloat($li.children('span').text()) * 100 / total

            $li.animate({ width: percent + '%' }, 500)
                .attr('title', $li.attr('title') + ' ' + $li.find('span').text())
                .find('span').text(Math.round(percent) + '%')
        })
    })

    // Infinite adding
    if ($('#addlist').length != 0) {
        var $template = $('#addlist li:last-child').css('display', 'none')

        $('#addlist + p').prepend($('<button/>', {
            text: 'Add Items',
            type: 'button'
        }).on('click', function() {
            var input = null

            for (var i = 0; i < 5; i++) {
                var $clone = $template.clone()
                $template.before($clone.css('display', 'block'))
                autosize($clone.find('textarea'))

                if (i == 0) input = $clone.find('input').get(0)
            }

            input.focus()

            return false
        }))
    }

    // Infinite set pagination
    if ($('.vocabularies li.more').length != 0) {
        var wireLink = function($link) {
            if ($link.length == 0) return

            $link.on('click', function() {
                if ($link.hasClass('disabled')) return false
                $link.addClass('disabled')

                $.get($link.attr('href'), function(data) {
                    $link.parent().removeClass('more').addClass('separator').text($('.separator').length + 1)
                        .after($(data).find('.vocabularies').eq(-1).html())

                    wireLink($('.vocabularies li.more a'))
                })

                return false
            })
        }

        wireLink($('.vocabularies li.more a'))
    }

    // Editing vocabularies
    if ($('#study').length == 0 && $('section.back + textarea, section.notes + textarea').length != 0) {
        $('section.back + textarea, section.notes + textarea').css('display', 'none')

        $('section.back').each(function() {
            if ($(this).find('.tasks').length != 0) return

            $(this).prepend($('<p/>', {
                class: 'tasks'
            }).append($('<a/>', {
                href: '#',
                text: 'Edit'
            }).on('click', function() {
                $('section.back, section.notes').css('display', 'none')
                $('section.back + textarea, section.notes + textarea')
                    .css('display', 'block').eq(0).focus()
                $(this).parents('form').find('button')
                    .css('display', 'inline-block')

                return false
            })))
        })

        $('section.back').parents('form').find('button').css('display', 'none')
        $('section.back').parents('form').find('button[type="reset"]').on('click', function() {
            $(this).parents('form').find('section.back, section.notes').css('display', 'block')
                .next('textarea').css('display', 'none')
            $(this).parents('form').find('button').css('display', 'none')
        })
    }
})
