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

### Manual update
Mediawiki requires you to run a update script occasionally (with changes to extensions or other database updates).

You can run this maintanance script manually by running the following command:
```sh
make update
```

### Manually running the job-queue
Mediawiki uses a job-queue based system for specific maintanance tasks, this command is automatically run inside the container by a cron-job. However you can manually run the maintanance script by running the following command:
```sh
make run-jobs
```

## Structure of repository
- docker-compose.yml - Docker compose configuration for development
- Dockerfile - Docker container build instructions
- conf/* - Software configuration files
- conf/LocalSettings.php - Main Mediawiki configuration file
- conf/LocalSettings/* - Additional Mediawiki configuration
- extensions/* - Mediawiki extensions (Git submodules)
## Licensing
```
This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.
```
