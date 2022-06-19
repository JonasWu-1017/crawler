#!/bin/bash

BASEDIR=`cd $(dirname $0); pwd`
cd $BASEDIR

CMD=$1
case $CMD in
    env)
        if [ ! -f ".env" ]; then
            sudo cp ".env.example" ".env" -a
        fi

        sudo apt update
        NODE_VER=`node -v`
        NODE_VER_N=${NODE_VER:1:2}
        if [ "${NODE_VER:1:2}" -lt "14" ]; then            
            sudo curl -sL https://deb.nodesource.com/setup_14.x | sudo bash -
            sudo apt-get install -y nodejs
        fi
        sudo apt-get install -y libgbm-dev
        sudo apt-get install -y libasound2
        sudo mkdir -p '/usr/lib/node_modules/puppeteer/.local-chromium'
        sudo npm install puppeteer --location=global
        
        # Install Packages
        sudo composer install -n
        sudo npm install -g npm
        sudo npm install laravel-mix@latest
    
        sudo php artisan config:cache
        sudo php artisan config:clear
        sudo php artisan cache:clear
        sudo php artisan route:cache
        sudo php artisan route:clear
        # Generate Application key
        sudo php artisan key:generate --force
        sudo php artisan storage:link
    ;;
    *)
        echo "Usage:"
        echo "   init.sh [commands]\n"
        echo "Commands:"
        echo "   env        Generate develop '.env' and run 'composer install'\n"
    ;;    
esac

echo "$@ finish!"
exit 0;
