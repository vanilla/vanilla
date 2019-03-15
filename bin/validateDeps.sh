echo -e "\n==================== Verifying Dependencies ===================="
node_path="$(which node)"
if [ -z $node_path ]; then
    echo -e "Node is not installed."
    echo -e "There is no point in continuing."
    exit
else
    echo -e "NODE VERSION: $(node --version)"
fi

yarn_path="$(which yarn)"
if [ -z $yarn_path ]; then
    echo -e "Yarn is not installed."
    echo -e "There is no point in continuing."
    exit
else
    echo -e "YARN VERSION: $(yarn --version)"
fi

php_path="$(which php)"
if [ -z $php_path ]; then
    echo -e "PHP is not installed."
    echo -e "There is no point in continuing."
    exit
else
    echo -e "$(php --version)"
fi

composer_path="$(which composer)"
if [ -z $php_path ]; then
    echo -e "Composer is not installed."
    echo -e "There is no point in continuing."
    exit
else
    echo -e "$(composer --version)"
fi


echo -e "\n==================== Preparing Command ===================="

# Ensure dependencies
# Composer install has everything else happening in a post-install script.
VANILLA_BUILD_DISABLE_AUTO_BUILD=true composer install
cd bin/scripts
yarn install
cd -