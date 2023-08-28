# Lumen PHP Framework

[![Build Status](https://travis-ci.org/laravel/lumen-framework.svg)](https://travis-ci.org/laravel/lumen-framework)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel/framework)](https://packagist.org/packages/laravel/lumen-framework)
[![Latest Stable Version](https://img.shields.io/packagist/v/laravel/framework)](https://packagist.org/packages/laravel/lumen-framework)
[![License](https://img.shields.io/packagist/l/laravel/framework)](https://packagist.org/packages/laravel/lumen-framework)

Laravel Lumen is a stunningly fast PHP micro-framework for building web applications with expressive, elegant syntax. We believe development must be an enjoyable, creative experience to be truly fulfilling. Lumen attempts to take the pain out of development by easing common tasks used in the majority of web projects, such as routing, database abstraction, queueing, and caching.

## Official Documentation

Documentation for the framework can be found on the [Lumen website](https://lumen.laravel.com/docs).

## Contributing

Thank you for considering contributing to Lumen! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Security Vulnerabilities

If you discover a security vulnerability within Lumen, please send an e-mail to Taylor Otwell at taylor@laravel.com. All security vulnerabilities will be promptly addressed.

## License

The Lumen framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


##Requirements:
PHP Version: 7.4.18
Composer version: 2.0.13
MariaDB: 10.05

##Installation Instructions

Step 1: Use the following code in order to install PHP 7.4.
	sudo apt -y install php7.4
	
	
Step 2: Use following commands to install and run composer.(1st command will install composer globally.) - skip this step if Composer is already installed in the setting up machine

	sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

	change the directory to the server using - cd/server  
	composer install - Run this command even if composer is installed
	composer -v  // Once the Installation is done do check the version to see if composer is installed correctly 
	composer update (optional)
	

Step 3: Rename the .env.example to .env
	
	mv .env.example .env

Step 4: Install Maria DB 
		
	Local Maria DB Installtion 

	if you wish to install Maria DB locally refer = https://www.digitalocean.com/community/tutorials/how-to-install-mariadb-on-ubuntu-20-04
	
	once maria db is installed and configured according to the above link run the below code in the Terminal to ensure if its installed 
```sh
	sudo systemctl status mariadb
```
	Docker Maria DB Installation

	if you wish to install Maria DB in a docker container refer = https://mariadb.com/kb/en/installing-and-using-mariadb-via-docker/

	Once the docker configurations are completed run the below code in the Terminal 
```sh
	docker inspect "${ContainerName}"  
```
	Copy the IP address and add it to the backend .env file 


Step 5: Create a database with the following name 
	
	DB_NAME = iconnhrm_two
	
	
Step 6: Configure the .env file by changing the values of the following,
	
	APP_KEY= base64:iJVNSY+fOaBgZx1qlVW5k4qCmLeRbCRd6FrfV+soW5U
	DB_DATABASE= iconnhrm_two
	
		
	Change the DB_USERNAME and DB_PASSWORD based on your credentials.


Step 7: Run following command to migrate the database.

	php artisan migrate
	
	
Step 8: Run the following command to run the app in the development server.

	php -S localhost:8080 -t public

### Setting up object storage

For development purposes use Minio which is an S3 compatible object storage.

Easiest way to setup minio is with docker.

```bash
  docker run -p 9000:9000 \   
  --name iconnhrm2-minio-server -d \
  -v /mnt/data:/data \
  -e "MINIO_ACCESS_KEY=AKIAIOSFODNN7EXAMPLE" \
  -e "MINIO_SECRET_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY" \
  minio/minio server /data
```
Read the [minio guide](https://docs.min.io/docs/minio-quickstart-guide.html) for additional details

> ## Running Locally
	
##Specific Commands.
1. php artisan queue:work
	Use the above command in order to process jobs. e.g. Sending an email using "App\Jobs\EmailNotificationJob".


##Bulk upload configration 

-Install the dependances using the below command 
	$ composer install

-Manually publish the excel service provider using the below command
	$ php artisan vendor:publish

-Once the above command is executed you will be prompted with a options menu
	Select the option with Maatwebsite\Excel\ExcelServiceProvider 

