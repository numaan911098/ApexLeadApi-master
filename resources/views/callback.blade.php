<html>

<head>
    <meta charset="utf-8">
    <script>
        if (window.opener != null && !window.opener.closed) {
            let response = @json($data);
            this.opener.postMessage(response, "*");
        }
    </script>
</head>

<body>
</body>

</html>
