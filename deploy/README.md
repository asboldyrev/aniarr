# Aniarr background services

Aniarr uses user-level systemd services for long-running processes and cron for the Laravel scheduler.

## Processes

`aniarr.target` starts:

- `aniarr-queue-default@1.service`
- `aniarr-queue-default@2.service`
- `aniarr-queue-downloads@1.service`
- `aniarr-queue-downloads@2.service`
- `aniarr-reverb.service`

The Laravel scheduler is executed by the current user's crontab every minute.

## Install

Before installing, stop `composer dev` or any manually started queue/Reverb processes to avoid duplicate workers and a Reverb port conflict.

```bash
make services-install
```

On the first install the Makefile may ask for `sudo` to enable systemd user lingering for `boldyreva`. Lingering is required for user services to start at boot without an interactive login session.

## Management

```bash
make systemd-status
make systemd-logs
make systemd-restart
make systemd-stop
make systemd-start
make cron-show
```

## Uninstall

```bash
make services-uninstall
```

The uninstall command removes only Aniarr systemd units and the Aniarr scheduler cron entry. It intentionally does not disable user lingering because other user services may depend on it.

## Queue timeout

Background queue units export `DB_QUEUE_RETRY_AFTER=600`. This keeps the database queue retry window longer than Aniarr's long-running import job timeout and prevents another worker from reclaiming the same job too early.
