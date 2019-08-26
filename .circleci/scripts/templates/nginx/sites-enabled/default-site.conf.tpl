
server {

    server_name $hostname;
    listen 8080 default_server;

    root {ROOT};
    index index.html index.htm index.php;
    access_log /tmp/access.log;
    error_log /tmp/error.log; # debug;
    # rewrite_log on;

    # Safeguard against serving configs
    location ~* "/\.htaccess$" { deny all; return 403; }
    location ~* "/\.git" { deny all; return 403; }
    location ~* "/cache/.*$" { deny all; return 403; }
    location ~* "/conf/.*$" { deny all; return 403; }
    location ^~ "/favicon.ico" { access_log off; log_not_found off; return 404; }


    # Basic PHP handler
    location ~* "^/_index\.php(/|$)" {
        internal;
        set $downstream_handler php;

        # send to fastcgi
        include fastcgi.conf;
        fastcgi_param X_REWRITE $x_rewrite;
        fastcgi_param SCRIPT_NAME /index.php;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_param DOCUMENT_URI $fastcgi_path_info;
        fastcgi_pass php;
    }

    # Handle an explicit index.php?p= url.
    location ~* "^/index\.php$" {
        set $x_rewrite 1;
        if ($arg_p ~* "^/") {
            rewrite ^ /_index.php$arg_p last;
        }

        rewrite ^ /_index.php$uri;
    }

    # PHP
    location ~* "^/cgi-bin/.+\.php(/|$)" {
        # root {ROOT};
        set $downstream_handler php;

        # send to fastcgi
        include fastcgi.conf;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass php;
    }

    # Don't let any other php files run by themselves
    location ~* "\.php(/|$)" {
        set $x_rewrite 1;
        rewrite ^ /_index.php$uri last;
    }

    # Default location
    location / {
        set $downstream_handler nginx;
        try_files $uri @vanilla;
    }

    location @vanilla {
        set $x_rewrite 1;
        rewrite ^ /_index.php$uri last;
    }

}
