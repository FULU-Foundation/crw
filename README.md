# Consumer Rights Wiki

## Introduction

This repository contains the docker container, software configuration and development tools for the Consumer Rights Wiki.

## Development usage

### Required software
- Docker
- make

For Windows users we recommend using the Windows Subsystem for Linux (WSL).

### Initial setup
1. Clone submodules
    ```sh
    git submodule init
    git submodule update
    ```
2. Configure a .env file (see .env.example for reference).
3. Start the development instance
    ```sh
    make run
    ```
4. Initiate the database
    ```sh
    make init
    ```
5. Run the update script (initialise all other tables)
    ```sh
    make update
    ```

### Manual update
MediaWiki requires you to run an update script occasionally (with changes to extensions or other database updates).

You can run this maintenance script manually by running the following command:
```sh
make update
```

### Manually running the job-queue
MediaWiki uses a job-queue based system for specific maintenance tasks, this command is automatically run inside the container by a cron-job. However you can manually run the maintenance script by running the following command:
```sh
make run-jobs
```

## Structure of repository
```
crw/
├── conf/                   # Software configuration files
│   ├── LocalSettings.php   # Main MediaWiki configuration file
│   └── LocalSettings/      # Additional MediaWiki configuration
├── cron/                   # Scheduled maintenance scripts
├── data/                   # Static assets served by the wiki
├── extensions/             # MediaWiki extensions (Git submodules)
├── patches/                # Build-time patches
├── scripts/                # Scripts invoked by the Makefile
├── skins/                  # MediaWiki skins
├── tests/                  # Tests
├── docker-compose.yml      # Docker compose configuration for development
└── Dockerfile              # Docker container build instructions
```
## Licensing
```
This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.
```
