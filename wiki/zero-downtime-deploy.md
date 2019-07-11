## Deploy project using zero downtime configuration with Laravel Envoy script
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

### Initialize Project
Having an empty project folder in the server, ej `/var/www/turismo` you can run:
```bash
envoy run init --branch=master
```

now ssh into the server and fill `.env` file.

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

### Notes
> *You may update cron and supervisor configurations to point to `/var/www/turismo/current/` directory*