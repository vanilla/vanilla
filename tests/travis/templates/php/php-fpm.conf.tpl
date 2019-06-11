[global]

[travis]
listen = {PORT}
listen.mode = 0666
pm = static
pm.max_children = 5
php_admin_value[memory_limit] = 32M
php_admin_value[log_errors_max_len] = 0

[circleci]
listen = {PORT}
listen.mode = 0666
pm = static
pm.max_children = 5
php_admin_value[memory_limit] = 2000M
php_admin_value[log_errors_max_len] = 0
