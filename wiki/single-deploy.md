### Deploy project using single Laravel Envoy script
> *Note*  This script HAVE DOWNTIME while deploy is executed!

You can deploy a project with a single laravel Envoy script, assuming you have a single project structure:
```
* -- /var/www/turismo
          |---------- app
          |---------- ...
          |---------- routes
          |---------- ...
          |---------- .env
          |---------- storage
          |---------- ...
```

Now add the new variables from [`.env.example` file](/.env.example) to your `.env` file

> *Note: you must have a ssh key with access to the pointed server into your `.ssh/config` file, and the repo url must be ssh also*

Now add the [`Envoy.blade.php` file](/single-deploy/Envoy.blade.php) on the project root folder, you can comment and uncomment sentences what do you need

### Initiialize Project
Now, having a empty project folder in the server, ej `/var/www/turismo` you can run:
```bash
git init
git remote add origin gitrepourlhere
```

now you must fill `.env` file.

### Deploy Project
From your computer you can run deploys using this command:
```bash
envoy run deploy --branch=master
```

### Rollback Project
If you need do some rollback use:
```bash
envoy run rollback --branch=master
```