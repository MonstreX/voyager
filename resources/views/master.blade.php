<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" dir="{{ __('voyager::generic.is_rtl') == 'true' ? 'rtl' : 'ltr' }}">
<head>
    <title>@yield('page_title', setting('admin.title') . " - " . setting('admin.description'))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="assets-path" content="{{ voyager_asset() }}"/>
    <meta name="voyager-ace-base" content="{{ voyager_asset('js/ace/libs') }}"/>
    <script>
        window.voyagerAceBase = "{{ voyager_asset('js/ace/libs') }}";

        const voyagerAssetsMeta = document.head.querySelector('meta[name="assets-path"]');
        const voyagerAssetsBase = voyagerAssetsMeta ? voyagerAssetsMeta.getAttribute('content').replace(/\/?$/, '/') : '{{ rtrim(voyager_asset(''), '/') }}/';

        function voyagerLoadModule(relativePath, cacheKey, rejectHandler) {
            const normalizedPath = (relativePath || '').replace(/^\//, '');
            const key = '__voyagerModule_' + cacheKey;
            if (window[key]) {
                return window[key];
            }
            const moduleUrl = voyagerAssetsBase + normalizedPath;
            window[key] = import(moduleUrl).catch(function(error) {
                console.error('[Voyager] Failed to load ' + normalizedPath, error);
                if (typeof rejectHandler === 'function') {
                    rejectHandler(error);
                }
                throw error;
            });
            return window[key];
        }

        // Pre-initialize Voyager namespace and readiness Promises
        // This MUST run BEFORE any inline scripts or ES module loads
        window.Voyager = window.Voyager || {};
        window.Voyager.ready = window.Voyager.ready || {};

        // Create Promises with external resolvers (modules will call these)
        window.Voyager.ready.app = new Promise(function(resolve, reject) {
            window.__resolveAppReady = resolve;
            window.__rejectAppReady = reject;
        });
        window.Voyager.ready.vue = new Promise(function(resolve, reject) {
            window.__resolveVueReady = resolve;
            window.__rejectVueReady = reject;
        });
        window.Voyager.ready.editors = new Promise(function(resolve, reject) {
            window.__resolveEditorsReady = resolve;
            window.__rejectEditorsReady = reject;
        });

        if (typeof window.Voyager.loadVue !== 'function') {
            window.Voyager.loadVue = function() {
                return voyagerLoadModule('js/vue-bundle.js', 'vue', window.__rejectVueReady);
            };
        }

        if (typeof window.Voyager.loadEditors !== 'function') {
            window.Voyager.loadEditors = function() {
                return voyagerLoadModule('js/editors.js', 'editors', window.__rejectEditorsReady);
            };
        }

        if (typeof window.Voyager.withVue !== 'function') {
            window.Voyager.withVue = function(callback) {
                return window.Voyager.loadVue().then(function() {
                    var api = (window.Voyager && window.Voyager.vue) ? window.Voyager.vue : {
                        createApp: window.createVueApp,
                        registerComponent: window.VueRegisterComponent,
                        mountApp: window.VueMountApp
                    };
                    var result = {
                        createApp: api.createApp || window.createVueApp || function() {},
                        registerComponent: api.registerComponent || window.VueRegisterComponent || function() {},
                        mountApp: api.mountApp || window.VueMountApp || function() {}
                    };
                    if (typeof callback === 'function') {
                        callback(result);
                    }
                    return result;
                }).catch(function(error) {
                    console.error('[Voyager] Failed to prepare Vue helpers', error);
                    throw error;
                });
            };
        }

        window.__voyagerDeprecationWarned = {};
        function warnDeprecated(api, replacement) {
            if (window.__voyagerDeprecationWarned[api]) {
                return;
            }
            window.__voyagerDeprecationWarned[api] = true;
            if (window.console && typeof window.console.warn === 'function') {
                window.console.warn('[Voyager] ' + api + ' is deprecated. Use ' + replacement + ' instead.');
            }
        }

        // Helpers to wait for bundles to load (deprecated)
        window.whenAppReady = function(callback) {
            warnDeprecated('whenAppReady()', 'Voyager.ready.app.then');
            window.Voyager.ready.app.then(callback);
        };

        window.whenVueReady = function(callback) {
            warnDeprecated('whenVueReady()', 'Voyager.loadVue().then');
            window.Voyager.withVue(function() {
                if (typeof callback === 'function') {
                    callback();
                }
            });
        };

        window.whenEditorsReady = function(callback) {
            warnDeprecated('whenEditorsReady()', 'Voyager.loadEditors().then');
            var loader = window.Voyager && typeof window.Voyager.loadEditors === 'function'
                ? window.Voyager.loadEditors()
                : Promise.resolve();
            loader.then(function() {
                return window.Voyager.ready.editors;
            }).then(callback);
        };
    </script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">

    <!-- Favicon -->
    <?php $admin_favicon = Voyager::setting('admin.icon_image', ''); ?>
    @if($admin_favicon == '')
        <link rel="shortcut icon" href="{{ voyager_asset('images/logo-icon.png') }}" type="image/png">
    @else
        <link rel="shortcut icon" href="{{ Voyager::image($admin_favicon) }}" type="image/png">
    @endif



    <!-- App CSS -->
    <link rel="stylesheet" href="{{ voyager_asset('css/app.css') }}">

    @yield('css')
    @if(__('voyager::generic.is_rtl') == 'true')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-rtl/3.4.0/css/bootstrap-rtl.css">
        <link rel="stylesheet" href="{{ voyager_asset('css/rtl.css') }}">
    @endif

    <!-- Few Dynamic Styles -->
    <style type="text/css">
        .voyager .side-menu .navbar-header {
            background:{{ config('voyager.primary_color','#22A7F0') }};
            border-color:{{ config('voyager.primary_color','#22A7F0') }};
        }
        .widget .btn-primary{
            border-color:{{ config('voyager.primary_color','#22A7F0') }};
        }
        .widget .btn-primary:focus, .widget .btn-primary:hover, .widget .btn-primary:active, .widget .btn-primary.active, .widget .btn-primary:active:focus{
            background:{{ config('voyager.primary_color','#22A7F0') }};
        }
        .voyager .breadcrumb a{
            color:{{ config('voyager.primary_color','#22A7F0') }};
        }
    </style>

    @if(!empty(config('voyager.additional_css')))<!-- Additional CSS -->
        @foreach(config('voyager.additional_css') as $css)<link rel="stylesheet" type="text/css" href="{{ asset($css) }}">@endforeach
    @endif

    @yield('head')
</head>

<body class="voyager @if(isset($dataType) && isset($dataType->slug)){{ $dataType->slug }}@endif">

<div id="voyager-loader">
    <?php $admin_loader_img = Voyager::setting('admin.loader', ''); ?>
    @if($admin_loader_img == '')
        <img src="{{ voyager_asset('images/logo-icon.png') }}" alt="Voyager Loader">
    @else
        <img src="{{ Voyager::image($admin_loader_img) }}" alt="Voyager Loader">
    @endif
</div>

<?php
if (\Illuminate\Support\Str::startsWith(Auth::user()->avatar, 'http://') || \Illuminate\Support\Str::startsWith(Auth::user()->avatar, 'https://')) {
    $user_avatar = Auth::user()->avatar;
} else {
    $user_avatar = Voyager::image(Auth::user()->avatar);
}
?>

<div class="app-container">
    <div class="fadetoblack visible-xs"></div>
    <div class="row content-container">
        @include('voyager::dashboard.navbar')
        @include('voyager::dashboard.sidebar')
        <script>
            (function(){
                    var appContainer = document.querySelector('.app-container'),
                        sidebar = appContainer.querySelector('.side-menu'),
                        navbar = appContainer.querySelector('nav.navbar.navbar-top'),
                        loader = document.getElementById('voyager-loader'),
                        hamburgerMenu = document.querySelector('.hamburger'),
                        sidebarTransition = sidebar.style.transition,
                        navbarTransition = navbar.style.transition,
                        containerTransition = appContainer.style.transition;

                    sidebar.style.WebkitTransition = sidebar.style.MozTransition = sidebar.style.transition =
                    appContainer.style.WebkitTransition = appContainer.style.MozTransition = appContainer.style.transition =
                    navbar.style.WebkitTransition = navbar.style.MozTransition = navbar.style.transition = 'none';

                    if (window.innerWidth > 768 && window.localStorage && window.localStorage['voyager.stickySidebar'] == 'true') {
                        appContainer.className += ' expanded no-animation';
                        loader.style.left = (sidebar.clientWidth/2)+'px';
                        hamburgerMenu.className += ' is-active no-animation';
                    }

                   navbar.style.WebkitTransition = navbar.style.MozTransition = navbar.style.transition = navbarTransition;
                   sidebar.style.WebkitTransition = sidebar.style.MozTransition = sidebar.style.transition = sidebarTransition;
                   appContainer.style.WebkitTransition = appContainer.style.MozTransition = appContainer.style.transition = containerTransition;
            })();
        </script>
        <!-- Main Content -->
        <div class="container-fluid">
            <div class="side-body padding-top">
                @yield('page_header')
                <div id="voyager-notifications"></div>
                @yield('content')
            </div>
        </div>
    </div>
</div>
@include('voyager::partials.app-footer')

<!-- Javascript Libs -->

<!-- Load app bundle (core functionality) -->
<script type="module" src="{{ voyager_asset('js/app.js') }}"></script>

<script>
    // Display Laravel session alerts and messages
    window.Voyager.ready.app.then(function() {
        @if(Session::has('alerts'))
            let alerts = {!! json_encode(Session::get('alerts')) !!};
            helpers.displayAlerts(alerts, toastr);
        @endif

        @if(Session::has('message'))
        // TODO: change Controllers to use AlertsMessages trait... then remove this
        var alertType = {!! json_encode(Session::get('alert-type', 'info')) !!};
        var alertMessage = {!! json_encode(Session::get('message')) !!};
        var alerter = toastr[alertType];

        if (alerter) {
            alerter(alertMessage);
        } else {
            toastr.error("toastr alert-type " + alertType + " is unknown");
        }
        @endif
    });
</script>
@include('voyager::media.manager')
<script>
// Mount any declarative Vue roots (if present)
const autoVueRoots = document.querySelectorAll('[data-voyager-vue-root]');
if (autoVueRoots.length && window.Voyager && typeof window.Voyager.loadVue === 'function') {
    window.Voyager.loadVue().then(() => {
        if (window.VueMountApp) {
            window.VueMountApp(autoVueRoots);
        }
    }).catch(() => {});
}
</script>
@yield('javascript')
@stack('javascript')
@if(!empty(config('voyager.additional_js')))<!-- Additional Javascript -->
    @foreach(config('voyager.additional_js') as $js)<script type="text/javascript" src="{{ asset($js) }}"></script>@endforeach
@endif

</body>
</html>
