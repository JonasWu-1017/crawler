#!/bin/sh

BASEDIR=`cd $(dirname $0); pwd`
cd $BASEDIR

CMD=$1
case $CMD in
    env)
        if [ ! -f ".env" ]; then
            sudo cp ".env.example" ".env" -a
        fi
        
        # Install Packages
        if [ -f "/usr/local/bin/composer" ]; then
            sudo /usr/local/bin/composer install -n
        fi
        
        sudo php artisan config:cache
        sudo php artisan config:clear
        sudo php artisan cache:clear
        sudo php artisan route:cache
        sudo php artisan route:clear
        # Generate Application key
        sudo php artisan key:generate --force
        sudo php artisan storage:link
    ;;
    run)
        sudo npm run dev
        sudo php artisan serve
    ;;
    *)
        echo "Usage:"
        echo "   init.sh [commands]\n"
        echo "Commands:"
        echo "   env        Generate develop '.env' and run 'composer install'\n"
        echo "   run        npm run dev & php artisan serve'\n"
    ;;    
esac

echo "$@ finish!"
exit 0;
