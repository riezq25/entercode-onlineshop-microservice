# Test Skill Backend Developer Entercode
## Requirement
 - PHP 8.2
 - Composer 2
 - Node.js & NPM
 - MySQL or any other supported database

## Instalation
 1. Clone the repository:

    git clone https://github.com/riezq25/entercode-onlineshop-microservice.git

 2. Install PHP dependencies:

    composer install

 3. Install JavaScript dependencies:

    npm install && npm run dev

 4.  Copy the `.env.example` file and configure your environment variables:

    cp .env.example .env

 5. Generate application key:

    php artisan key:generate

 6.  Migrate the database:

    php artisan migrate

## Email Configuration
 1. Open the `.env` file.
 2. Set your mail driver, for example:

    MAIL_MAILER=smtp
	MAIL_HOST=smtp.mailtrap.io
	MAIL_PORT=2525
	MAIL_USERNAME=your_mailtrap_username
	MAIL_PASSWORD=your_mailtrap_password
	MAIL_ENCRYPTION=tls
	MAIL_FROM_ADDRESS=your_email@example.com
	MAIL_FROM_NAME="${APP_NAME}"

Replace the values with your email service provider credentials.

## Running the app
Run the following command to running the app:

    php artisan serve

## Postman collection
1.  Download and install [Postman](https://www.postman.com/downloads/).
2.  Import the Postman collection file provided in the repository (`public/collection.json`).
3.  Use the endpoints with appropriate request parameters to interact with the API.
