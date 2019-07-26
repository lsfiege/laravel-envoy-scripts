# Deploy project using zero downtime configuration with Laravel Envoy script

First of all you must [configure SSH in your server](./wiki/configure-ssh.md)

For use zero downtime deploys, you need to structure project folder in a different way:
```
* -- /var/www/turismo
          |---------- current --> /var/www/turismo/releases/latestrelease
          |---------- .env
          |---------- releases
          |---------- storage
```

Also you need to update the virtual host configuration to point to `/var/www/turismo/current/public`


Now add the new variables from [`.env.example` file](/.env.example) to your `.env` file

Now add an [`Envoy.blade.php` file](/zero-downtime/Envoy.blade.php) on the project root folder, you can comment and uncomment sentences what do you need

### Notes
> **1** You may update cron and supervisor configurations to point to `/var/www/turismo/current/` directory*

> **2** Optionally in any command you can specify the branch to use, for example:

```bash
envoy run deploy --branch=master
```

---

## Initialize Project
Having an empty project folder in the server, ej `/var/www/turismo` you can run:
```bash
envoy run init
```

now ssh into the server and fill `.env` file.

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