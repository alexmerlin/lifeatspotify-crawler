# lifeatspotify.com Crawler
Crawler demo for lifeatspotify.com jobs

## Setup:
This is a Symfony 5 Skeleton project. In order to use it, first, you need to install its dependencies by running:
```bash
composer install
```

## Crawling jobs:
Running the command:
```php
php bin/console job:crawl -l stockholm -l gothenburg
```
will crawl jobs for Sweden - specified locations being Stockholm and Gothenburg

The base command is:
```php
php bin/console job:crawl
```
(this will crawl all jobs from Spotify - all locations, all categories, all job types) - takes a couple of minutes to run.

Additionally, you can add:
- `-l` to filter by location (`php bin/console job:crawl -l stockholm`)
- `-c` to filter by category (example: `php bin/console job:crawl -c engineering`)
- `-j` to filter by job type (example: `php bin/console job:crawl -j internship`)
You can also combine the filters and add as many as you want of each of them.
Example:
```php
php bin/console job:crawl -l stockholm -l gothenburg -c engineering -c product -j permanent -j internship
```

The results will be both outputted to the console and dumped to a local file (data/jobs.json).
The script always writes to the same file, so the file will get overwritten with each run.


## Viewing crawled jobs:
This looks for jobs in the local file (`data/jobs.json`) - so, in order to view details about a job, the crawler needs to be run first.
While in the same directory (symfony-skeleton/), after you replace <id> with a job id, you can run:
```php
php bin/console job:view <id>
```
You can find a job id by looking in the local file, which holds a list of objects, each object has a property called id.
If you know that a job has been already crawled, you can also copy it's id directly from the site.

Example: https://www.lifeatspotify.com/jobs/av-event-production-engineer job id is: `av-event-production-engineer`
So, in this case the command would look like:
```php
php bin/console job:view av-event-production-engineer
```
