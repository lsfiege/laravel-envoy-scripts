# Deploy project using single Laravel Envoy script
> *Note*  This script may have DOWNTIME while deploy is executed!

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

Now add the [`Envoy.blade.php` file](/single-deploy/Envoy.blade.php) on the project root folder, you can comment and uncomment sentences what do you need

### Notes
> **1** You may update cron and supervisor configurations to point to `/var/www/turismo/` directory*

> **2** Optionally in any command you can specify the branch to use, for example:

```bash
envoy run deploy --branch=master
```

---

## Initialize Project
Now, having a empty project folder in the server, ej `/var/www/turismo` you can run:
```bash
git init
git remote add origin gitrepourlhere
```

now you must fill `.env` file.

## Deploy Project
From your computer you can run deploys using this command:
```bash
envoy run deploy
```

## Rollback Project
If you need do some rollback use:
```bash
envoy run rollback
```

---

## Migrations
### Run migrations
When executing the `deploy` command, you will be asked for confirmation to run `migrate` command. But also you can run this command manually:
```bash
envoy run migrate
```

### Rollback migrations
You can rollback your migrations using:
```bash
envoy run migrate_rollback
```

### Check migrations status
Also you can check the migrations status running
```bash
envoy run migrate_status
```

---

## Services
### Reload Services
You can reload the services that you define in the `reload_services` task executing:
```bash
envoy run reload_services
```