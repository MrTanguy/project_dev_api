[![Generic badge](https://img.shields.io/badge/Type-API-green.svg)](https://shields.io/) 
[![Generic badge](https://img.shields.io/badge/Language-php_8.1-blue.svg)](https://shields.io/)
[![Generic badge](https://img.shields.io/badge/Framework-symfony_6.1-purple.svg)](https://shields.io/)

# Find Professional API

This API is a study project carried out within Lyon Ynov Campus.

The purpose of this application is to find a company near you based on the service you are looking for,
you can also consult the note of the employees of a company and rate them. 



## Run Locally

Clone the project

```bash
  git clone https://github.com/MrTanguy/project_dev_api
```

Go to the project directory

```bash
  cd project_dev_api
```

Install dependencies

```bash
  composer Install
```

Create a 'jwt' directory inside 'config' directory, then run the command
```bash
    php bin/console lexik:jwt:generate-keypair
```

Setup your .env.local file
```bash
# DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
# DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8&charset=utf8mb4"
# DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=14&charset=utf8"

#JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
#JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
#JWT_PASSPHRASE=your pass phrase
```

Update database schema
```bash
  php bin/console doctrine:schema:update --force
```

If you dont have real data, you can use data fixtures with
```bash
  php bin/console doctrine:fixture:load
```

Start server
```bash
  symfony serve
```


## API Documentation
Once your local server is running go on
[FindProfessionals API documentation](http://localhost:8000/api/doc).


## Authors

- [@MrTanguy](https://github.com/MrTanguy)
- [@HPOIZAT](https://github.com/HPOIZAT)

