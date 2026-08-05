<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PHPagebuilder Authentication">
    <meta name="author" content="PHPagebuilder">
    <meta name="theme-color" content="#0d6efd">

    <title><?= phpb_trans('auth.title') ?></title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="<?= phpb_asset('pagebuilder/bootstrap-v4.3.1.min.css') ?>">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- App CSS -->
    <link rel="stylesheet" href="<?= phpb_asset('auth/app.css') ?>">

    <style>
        :root{
            --primary:#0d6efd;
            --bg:#f8f9fa;
            --text:#212529;
            --footer:#6c757d;
        }

        [data-theme="dark"]{
            --bg:#121212;
            --text:#f8f9fa;
            --footer:#bdbdbd;
        }

        body{
            font-family:'Inter',sans-serif;
            background:var(--bg);
            color:var(--text);
            min-height:100vh;
            display:flex;
            flex-direction:column;
            transition:.3s ease;
        }

        .main-container{
            flex:1;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:40px 15px;
        }

        footer{
            color:var(--footer);
        }

        .fade-page{
            animation:fadeIn .5s ease;
        }

        @keyframes fadeIn{
            from{
                opacity:0;
                transform:translateY(15px);
            }
            to{
                opacity:1;
                transform:none;
            }
        }

        #loader{
            position:fixed;
            inset:0;
            background:#fff;
            display:flex;
            justify-content:center;
            align-items:center;
            z-index:9999;
        }

        .dark-toggle{
            position:fixed;
            top:20px;
            right:20px;
            border:none;
            background:var(--primary);
            color:#fff;
            width:45px;
            height:45px;
            border-radius:50%;
            cursor:pointer;
            box-shadow:0 5px 15px rgba(0,0,0,.2);
        }

        @media(max-width:768px){
            .main-container{
                padding:20px;
            }
        }
    </style>
</head>

<body>

<div id="loader">
    <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
    </div>
</div>

<button class="dark-toggle" id="themeToggle" title="Toggle Theme">
    <i class="fas fa-moon"></i>
</button>

<div class="container fade-page">

    <main class="main-container">

        <?php
            require __DIR__ . '/' . $viewFile . '.php';
        ?>

    </main>

    <footer class="text-center py-4">

        <small>
            &copy; <?= date('Y') ?>
            Powered by
            <a href="https://github.com/HansSchouten/PHPagebuilder"
               target="_blank"
               rel="noopener noreferrer">
                PHPagebuilder
            </a>
        </small>

    </footer>

</div>

<script src="<?= phpb_asset('pagebuilder/jquery-3.4.1.min.js') ?>" defer></script>
<script src="<?= phpb_asset('pagebuilder/bootstrap-v4.3.1.min.js') ?>" defer></script>

<script>
document.addEventListener("DOMContentLoaded", () => {

    // Hide loader
    const loader = document.getElementById("loader");
    loader.style.opacity = "0";
    loader.style.transition = "opacity .4s";

    setTimeout(() => {
        loader.remove();
    }, 400);

    // Theme
    const html = document.documentElement;
    const btn = document.getElementById("themeToggle");

    const savedTheme = localStorage.getItem("theme");

    if (savedTheme) {
        html.setAttribute("data-theme", savedTheme);
        btn.innerHTML = savedTheme === "dark"
            ? '<i class="fas fa-sun"></i>'
            : '<i class="fas fa-moon"></i>';
    }

    btn.addEventListener("click", () => {

        const dark = html.getAttribute("data-theme") === "dark";

        if (dark) {
            html.setAttribute("data-theme", "light");
            btn.innerHTML = '<i class="fas fa-moon"></i>';
            localStorage.setItem("theme", "light");
        } else {
            html.setAttribute("data-theme", "dark");
            btn.innerHTML = '<i class="fas fa-sun"></i>';
            localStorage.setItem("theme", "dark");
        }

    });

});
</script>

</body>
</html>
