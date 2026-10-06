<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Widget preview</title>
<style>
body{margin:0;font:16px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:#14213D;background:#FBF9F4}
main{max-width:640px;margin:12vh auto;padding:0 20px}
h1{font-size:28px;margin:0 0 8px}
p{color:#4A5578}
a{color:#2B5FE2}
code{background:#E7ECF5;padding:2px 6px;border-radius:6px}
</style>
</head>
<body>
<main>
    <h1>Widget preview</h1>
    <p>This is a blank page that embeds <strong>{{ $workspaceName }}</strong>'s chat widget, exactly like a customer's website would.</p>
    <p>Send a message from the chat button in the corner, then open the <a href="{{ route('app.inbox') }}">Inbox</a> in another tab and reply to it. Your reply shows up here within a few seconds.</p>
    <p><a href="{{ route('app.settings.installation') }}">&larr; Back to Installation</a></p>
</main>
<script src="{{ $src }}" async></script>
</body>
</html>
