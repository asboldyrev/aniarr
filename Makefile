SHELL := /bin/bash

PROJECT_DIR := /home/boldyreva/data/Projects/pet/aniarr
PHP_BIN := /usr/bin/php8.5
SYSTEMD_USER_DIR := /home/boldyreva/.config/systemd/user
SYSTEMD_SOURCE_DIR := $(PROJECT_DIR)/deploy/systemd
SYSTEMD_TARGET := aniarr.target
CRON_MARKER := ANIARR_SCHEDULER
CRON_COMMAND := * * * * * cd $(PROJECT_DIR) && $(PHP_BIN) artisan schedule:run >> /dev/null 2>&1 \# $(CRON_MARKER)

.PHONY: help services-install services-uninstall \
        systemd-install systemd-uninstall systemd-start systemd-stop systemd-restart systemd-status systemd-logs \
        cron-install cron-uninstall cron-show

help:
	@printf '\033[0;36m** Available commands **\033[0m\n\n'
	@printf '\033[0;32m%-24s\033[0m %s\n' 'services-install' 'install and start systemd services, install scheduler cron'
	@printf '\033[0;32m%-24s\033[0m %s\n' 'services-uninstall' 'stop/remove Aniarr systemd services and scheduler cron'
	@printf '\n'
	@printf '\033[0;32m%-24s\033[0m %s\n' 'systemd-install' 'install and start Aniarr user systemd services'
	@printf '\033[0;32m%-24s\033[0m %s\n' 'systemd-uninstall' 'stop, disable and remove Aniarr user systemd services'
	@printf '\033[0;32m%-24s\033[0m %s\n' 'systemd-start' 'start Aniarr background services'
	@printf '\033[0;32m%-24s\033[0m %s\n' 'systemd-stop' 'stop Aniarr background services'
	@printf '\033[0;32m%-24s\033[0m %s\n' 'systemd-restart' 'restart Aniarr background services'
	@printf '\033[0;32m%-24s\033[0m %s\n' 'systemd-status' 'show status of Aniarr background services'
	@printf '\033[0;32m%-24s\033[0m %s\n' 'systemd-logs' 'follow logs of Aniarr background services'
	@printf '\n'
	@printf '\033[0;32m%-24s\033[0m %s\n' 'cron-install' 'install Laravel scheduler cron entry'
	@printf '\033[0;32m%-24s\033[0m %s\n' 'cron-uninstall' 'remove only Aniarr scheduler cron entry'
	@printf '\033[0;32m%-24s\033[0m %s\n' 'cron-show' 'show installed Aniarr scheduler cron entry'

services-install: systemd-install cron-install
	@printf '\n\033[0;32mAniarr background services installed and started.\033[0m\n'

services-uninstall: cron-uninstall systemd-uninstall
	@printf '\n\033[0;32mAniarr background services removed.\033[0m\n'

systemd-install:
	@printf '=== Installing Aniarr user systemd units ... '
	@mkdir -p '$(SYSTEMD_USER_DIR)'
	@cp '$(SYSTEMD_SOURCE_DIR)'/aniarr.target '$(SYSTEMD_USER_DIR)'/
	@cp '$(SYSTEMD_SOURCE_DIR)'/aniarr-queue-default@.service '$(SYSTEMD_USER_DIR)'/
	@cp '$(SYSTEMD_SOURCE_DIR)'/aniarr-queue-downloads@.service '$(SYSTEMD_USER_DIR)'/
	@cp '$(SYSTEMD_SOURCE_DIR)'/aniarr-reverb.service '$(SYSTEMD_USER_DIR)'/
	@systemctl --user daemon-reload
	@if [ "$$(loginctl show-user boldyreva -p Linger --value 2>/dev/null)" != 'yes' ]; then \
		printf '\n=== Enabling systemd user linger (sudo required) ...\n'; \
		sudo loginctl enable-linger boldyreva; \
	fi
	@systemctl --user enable --now '$(SYSTEMD_TARGET)'
	@printf '\033[0;32mDONE\033[0m\n'

systemd-uninstall:
	@printf '=== Removing Aniarr user systemd units ... '
	@systemctl --user disable --now '$(SYSTEMD_TARGET)' >/dev/null 2>&1 || true
	@rm -f \
		'$(SYSTEMD_USER_DIR)/aniarr.target' \
		'$(SYSTEMD_USER_DIR)/aniarr-queue-default@.service' \
		'$(SYSTEMD_USER_DIR)/aniarr-queue-downloads@.service' \
		'$(SYSTEMD_USER_DIR)/aniarr-reverb.service'
	@systemctl --user daemon-reload
	@systemctl --user reset-failed >/dev/null 2>&1 || true
	@printf '\033[0;32mDONE\033[0m\n'
	@printf 'Linger was left unchanged because other user services may depend on it.\n'

systemd-start:
	@systemctl --user start '$(SYSTEMD_TARGET)'

systemd-stop:
	@systemctl --user stop '$(SYSTEMD_TARGET)'

systemd-restart:
	@systemctl --user restart '$(SYSTEMD_TARGET)'

systemd-status:
	@systemctl --user --no-pager status \
		aniarr.target \
		aniarr-queue-default@1.service \
		aniarr-queue-default@2.service \
		aniarr-queue-downloads@1.service \
		aniarr-queue-downloads@2.service \
		aniarr-reverb.service || true

systemd-logs:
	@journalctl --user -f \
		-u aniarr-queue-default@1.service \
		-u aniarr-queue-default@2.service \
		-u aniarr-queue-downloads@1.service \
		-u aniarr-queue-downloads@2.service \
		-u aniarr-reverb.service

cron-install:
	@printf '=== Installing Aniarr scheduler cron ... '
	@(crontab -l 2>/dev/null | grep -v '$(CRON_MARKER)' || true; echo '$(CRON_COMMAND)') | crontab -
	@printf '\033[0;32mDONE\033[0m\n'

cron-uninstall:
	@printf '=== Removing Aniarr scheduler cron ... '
	@if crontab -l >/dev/null 2>&1; then \
		crontab -l | grep -v '$(CRON_MARKER)' | crontab -; \
	fi
	@printf '\033[0;32mDONE\033[0m\n'

cron-show:
	@crontab -l 2>/dev/null | grep '$(CRON_MARKER)' || true
