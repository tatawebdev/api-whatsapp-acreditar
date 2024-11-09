<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    <title>Dashmix - Bootstrap 5 Admin Template &amp; UI Framework</title>

    <meta name="description" content="Dashmix - Bootstrap 5 Admin Template &amp; UI Framework created by pixelcave">
    <meta name="author" content="pixelcave">
    <meta name="robots" content="index, follow">

    <meta property="og:title" content="Dashmix - Bootstrap 5 Admin Template &amp; UI Framework">
    <meta property="og:site_name" content="Dashmix">
    <meta property="og:description"
        content="Dashmix - Bootstrap 5 Admin Template &amp; UI Framework created by pixelcave">
    <meta property="og:type" content="website">
    <meta property="og:url" content="">
    <meta property="og:image" content="">

    <link rel="shortcut icon" href="{{ asset('assets/media/favicons/favicon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192"
        href="{{ asset('assets/media/favicons/favicon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180"
        href="{{ asset('assets/media/favicons/apple-touch-icon-180x180.png') }}">

    <link rel="stylesheet" id="css-main" href="{{ asset('assets/css/dashmix.min.css') }}">
    <link rel="stylesheet" id="css-theme" href="{{ asset('assets/css/themes/xdream.min.css') }}">

    <link href="{{ asset('assets/plugins/alertify/alertify.min.css') }}" rel="stylesheet" type="text/css" />

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ url('/') }}">


</head>

<body>
    <div id="page-container" class="sidebar-o side-scroll">

        <nav id="sidebar" aria-label="Main Navigation">
            <div class="content-header bg-primary">
                <a class="fw-semibold text-white tracking-wide" href="index.html">
                    <span class="smini-visible">
                        D<span class="opacity-75">x</span>
                    </span>
                    <span class="smini-hidden">
                        Dash<span class="opacity-75">mix</span>
                        <span class="fw-normal">Chat</span>
                    </span>
                </a>
                <div>
                    <a class="d-lg-none text-white ms-2" data-toggle="layout" data-action="sidebar_close"
                        href="javascript:void(0)">
                        <i class="fa fa-times-circle"></i>
                    </a>
                </div>
            </div>

            <div class="js-sidebar-scroll">
                <div class="content-side">
                    <form class="push" action="db_chat.html" method="POST" onsubmit="return false;">
                        <div class="input-group">
                            <input class="form-control form-control-alt" placeholder="Search People..">
                            <span class="input-group-text input-group-text-alt">
                                <i class="fa fa-fw fa-search"></i>
                            </span>
                        </div>
                    </form>
                    <div id="conversations-container" class="block pull-x">
                        <!-- Online -->
                        <div class="block-content block-content-sm block-content-full bg-body-light">
                            <span class="text-uppercase fs-sm fw-bold">Online</span>
                        </div>
                        <div id="online-conversations" class="block-content px-0">
                            <ul class="nav-items"></ul>
                        </div>

                        <div class="block-content block-content-sm block-content-full bg-body-light">
                            <span class="text-uppercase fs-sm fw-bold">Busy</span>
                        </div>
                        <div id="busy-conversations" class="block-content px-0">
                            <ul class="nav-items"></ul>
                        </div>

                        <div class="block-content block-content-sm block-content-full bg-body-light">
                            <span class="text-uppercase fs-sm fw-bold">Away</span>
                        </div>
                        <div id="away-conversations" class="block-content px-0">
                            <ul class="nav-items"></ul>
                        </div>

                        <div class="block-content block-content-sm block-content-full bg-body-light">
                            <span class="text-uppercase fs-sm fw-bold">Offline</span>
                        </div>
                        <div id="offline-conversations" class="block-content px-0">
                            <ul class="nav-items"></ul>
                        </div>

                        <div class="block-content border-top">
                            <a class="btn w-100 btn-alt-primary" href="javascript:void(0)">
                                <i class="fa fa-fw fa-plus opacity-50 me-1"></i> Add People
                            </a>
                        </div>
                    </div>


                </div>

            </div>

        </nav>

        <main id="main-container">

            <div class="block hero flex-column mb-0 bg-body-dark">

                <div class="block-header w-100 bg-body-dark" style="min-height: 68px;">
                    <h3 class="block-title">
                        <img class="img-avatar img-avatar32" src="assets/media/avatars/avatar7.jpg" alt="">
                        <a class="fs-sm fw-semibold ms-2" href="javascript:void(0)">Lisa Smith</a>
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option">
                            <i class="fa fa-cog"></i>
                        </button>
                        <button type="button" class="btn-block-option d-lg-none" data-toggle="layout"
                            data-action="sidebar_toggle">
                            <i class="fa fa-users"></i>
                        </button>
                    </div>
                </div>

                <div class="js-chat-messages block-content block-content-full text-break overflow-y-auto w-100 flex-grow-1 px-lg-8 px-xlg-10 bg-body"
                    data-chat-id="5">
                    <div class="me-4">
                        <div
                            class="fs-sm d-inline-block fw-medium animated fadeIn bg-body-light border-3 px-3 py-2 mb-2 shadow-sm mw-100 border-start border-dark rounded-end">
                            How are you? I wanted to talk about the new project. Feel free to ping me when you find
                            some
                            time, thanks!
                            <div style="display: flex;flex-direction: row-reverse;">Lido</div>
                        </div>
                    </div>
                </div>


                <div class="js-chat-form block-content p-3 w-100 d-flex align-items-center bg-body-dark"
                    style="min-height: 70px; height: 70px;">
                    <form class="w-100" action="db_chat.html" method="POST">
                        <div class="input-group dropup">
                            <button type="button" class="btn btn-link d-sm-none" data-bs-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-plus"></i>
                            </button>
                            <button type="button" class="btn btn-link d-none d-sm-inline-block">
                                <i class="fa fa-file-alt"></i>
                            </button>
                            <button type="button" class="btn btn-link d-none d-sm-inline-block"
                                id="uploadImageButton">
                                <i class="fa fa-image"></i>
                            </button>
                            <button type="button" class="btn btn-link d-none d-sm-inline-block">
                                <i class="fa fa-microphone-alt"></i>
                            </button>
                            <button type="button" class="btn btn-link d-none d-sm-inline-block">
                                <i class="fa fa-smile"></i>
                            </button>
                            <!-- Campo de entrada de mensagem de texto -->
                            <input id='chat-mensagem' type="text"
                                class="js-chat-input form-control form-control-alt border-0 bg-transparent"
                                data-target-chat-id="1" placeholder="Type a message..">
                            <!-- Botão de envio de mensagem -->
                            <button type="submit" onclick="enviarMensagemWhatsApp()" class="btn btn-link">
                                <i class="fab fa-telegram-plane opacity-50"></i>
                                <span class="d-none d-sm-inline ms-1 fw-semibold">Send</span>
                            </button>

                            <!-- Campo oculto para upload de imagem -->
                            <input type="file" id="imageUpload" class="d-none" accept="image/*">
                        </div>
                    </form>



                </div>

            </div>

        </main>

    </div>
    <script src="{{ asset('assets/js/dashmix.app.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/be_comp_chat.min.js') }}"></script>

    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

    <script src="{{ asset('assets/plugins/alertify/alertify.min.js') }}"></script>
    <script src="{{ asset('assets/sistema/helpers.js') }}"></script>

    <script>
        const firebaseConfig = {
            apiKey: "{{ config('firebase.api_key') }}",
            authDomain: "{{ config('firebase.auth_domain') }}",
            projectId: "{{ config('firebase.project_id') }}",
            storageBucket: "{{ config('firebase.storage_bucket') }}",
            messagingSenderId: "{{ config('firebase.messaging_sender_id') }}",
            appId: "{{ config('firebase.app_id') }}"
        };
        const vapidKey = "{{ config('firebase.vapid_key') }}";
    </script>

    <script type="module" src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app.js"></script>
    <script type="module" src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging.js"></script>

    <script src="{{ asset('assets/sistema/chat/chat.js') }}"></script>

    <script type="module" src="{{ asset('assets/sistema/chat/fcm.js') }}"></script>
    <script src="{{ asset('assets/sistema/chat/app.js') }}"></script>


    <script>
        Dashmix.onLoad();
    </script>
</body>

</html>
