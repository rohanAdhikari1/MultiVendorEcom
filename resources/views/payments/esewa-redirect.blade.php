<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Redirecting to eSewa&hellip;</title>
    </head>
    <body onload="document.forms[0].submit()">
        <p>Redirecting you to eSewa to complete your payment&hellip;</p>

        <form method="POST" action="{{ $action }}">
            @foreach ($fields as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <noscript>
                <button type="submit">Continue to eSewa</button>
            </noscript>
        </form>
    </body>
</html>
