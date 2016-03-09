$(document).ready(function() {
    // Autoresize textareas
    autosize($('textarea'))

    // Prepare studying
    if ($('#study').length != 0) {
        var currentIndex = 0
        var correct = Object.keys(new Int8Array($('#study li').length)).map(function() { return false })

        var showCard = function(index) {
            $('#study li').css('display', 'none').eq(index).css('display', 'block')
            currentIndex = index
        }

        var nextCard = function() {
            if (correct.every(function(x) { return x })) return false
            var i = (currentIndex + 1) % correct.length

            while (correct[i]) {
                if (i + 1 == correct.length) i = -1
                i++
            }

            showCard(i)
        }

        window.nextCard = nextCard

        $('#study label, button[type="submit"]').css('display', 'none')
        showCard(0)
    }
})
