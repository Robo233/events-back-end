## For running
1. Install PHP from here: https://www.php.net/
2. Add PHP's location to your system's PATH environment variable.
3. Install Composer from here: https://getcomposer.org/
4. Add Composer's location to your system's PATH environment variable.
5. Install Node.js from here: https://nodejs.org/en
6. Install Laravel, using Composer: 'composer global require laravel/installer'
7. Add the Composer's bin directory(C:\Users\YourUsername\AppData\Roaming\Composer\vendor\bin) to your system's PATH.
8. Go to the project's directory and run the following commands, to install the dependencies:
composer install  
npm install  
npm run dev  
9. Set up the enviroment file: 'copy .env.example .env'
10. Create application key: 'php artisan key:generate
11. Create a symbolic link to the storage: 'php artisan storage:link'
12. Run the app: 'php artisan serve'
'
