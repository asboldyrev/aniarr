<?php

use App\Jobs\RssCheckAndDownloadJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(RssCheckAndDownloadJob::class)->everyThirtyMinutes();
