<?php

use App\Jobs\RssCheckAndDownloadJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new RssCheckAndDownloadJob)->everyThirtyMinutes();
