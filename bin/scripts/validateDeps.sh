echo -e "\n==================== VERIFYING DEPENDENCIES ===================="
node_path="$(which node)"
if [ -z $node_path ]; then
    echo "Node is not installed."
    echo "There is no point in continuing."
    exit
else
    echo -e "NODE VERSION: $(node --version)"
fi

yarn_path="$(which yarn)"
if [ -z $yarn_path ]; then
    echo "Yarn is not installed."
    echo "There is no point in continuing."
    exit
else
    echo -e "YARN VERSION: $(yarn --version)"
fi

php_path="$(which php)"
if [ -z $php_path ]; then
    echo "PHP is not installed."
    echo "There is no point in continuing."
    exit
else
    echo -e "$(php --version)"
fi

composer_path="$(which composer)"
if [ -z $php_path ]; then
    echo "Composer is not installed."
    echo "There is no point in continuing."
    exit
else
    echo -e "$(composer --version)"
fi
