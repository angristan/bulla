<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Authentication</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #22863a; }
        .error { color: #cb2431; }
    </style>
</head>
<body>
    <div class="container">
        @if($success)
            <p class="success">Authentication successful!</p>
            <p>This window will close automatically...</p>
        @else
            <p class="error">{{ $error ?? 'Authentication failed.' }}</p>
            <p>You can close this window.</p>
        @endif
    </div>

    <script>
        (function() {
            // Try to notify opener via postMessage (works in some browsers)
            if (window.opener) {
                try {
                    window.opener.postMessage({
                        type: 'marge-github-auth',
                        success: @json($success)
                    }, '*');
                } catch (e) {
                    // Ignore - parent will refresh on popup close anyway
                }
            }

            // Close popup after a short delay
            setTimeout(function() {
                window.close();
            }, 1000);
        })();
    </script>
</body>
</html>
