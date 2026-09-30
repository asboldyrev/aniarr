<?php

namespace App\Integrations\QBittorrent\Dto;

class Torrent
{
    public function __construct(
        public string $hash,
        public string $content_path,
        public string $save_path,
        public string $root_path,
        public int|float $eta,
        public int|float $progress,
        public string $state,
        public string $name,
    ) {}

    public static function makeFromResponse(array $response): self
    {
        return new self(
            hash: $response['hash'],
            content_path: $response['content_path'],
            save_path: $response['save_path'],
            root_path: $response['root_path'],
            eta: $response['eta'],
            progress: $response['progress'],
            state: $response['state'],
            name: $response['name'],
        );
    }
}

// "added_on" => 1786194860
// "amount_left" => 1737991271
// "auto_tmm" => true
// "availability" => 0
// "category" => ""
// "comment" => ""
// "completed" => 0
// "completion_on" => -1
// "content_path" => "/media/downloads/Otonari no Tenshi-sama 2 - AniLiberty [WEB-DL 1080p HEVC]"
// "dl_limit" => 0
// "dlspeed" => 0
// "download_path" => ""
// "downloaded" => 0
// "downloaded_session" => 0
// "eta" => 8640000
// "f_l_piece_prio" => false
// "force_start" => false
// "has_metadata" => true
// "hash" => "fe23d1da7bce319e24fec5800d42b57e8a74349f"
// "inactive_seeding_time_limit" => -2
// "infohash_v1" => "fe23d1da7bce319e24fec5800d42b57e8a74349f"
// "infohash_v2" => ""
// "last_activity" => 1786194860
// "magnet_uri" => "magnet:?xt=urn:btih:fe23d1da7bce319e24fec5800d42b57e8a74349f&dn=Otonari%20no%20Tenshi-sama%202%20-%20AniLiberty%20%5BWEB-DL%201080p%20HEVC%5D&xl=1737991271&tr=http%3A%2F%2Ftr.libria.fun%3A2710%2Fannounce&tr=http%3A%2F%2Fretracker.local%2Fannounce"
// "max_inactive_seeding_time" => -1
// "max_ratio" => -1
// "max_seeding_time" => 0
// "name" => "Otonari no Tenshi-sama 2 - AniLiberty [WEB-DL 1080p HEVC]"
// "num_complete" => 0
// "num_incomplete" => 0
// "num_leechs" => 0
// "num_seeds" => 0
// "popularity" => 0
// "priority" => 1
// "private" => false
// "progress" => 0
// "ratio" => 0
// "ratio_limit" => -2
// "reannounce" => 0
// "root_path" => "/media/downloads/Otonari no Tenshi-sama 2 - AniLiberty [WEB-DL 1080p HEVC]"
// "save_path" => "/media/downloads"
// "seeding_time" => 0
// "seeding_time_limit" => -2
// "seen_complete" => -1
// "seq_dl" => false
// "size" => 1737991271
// "state" => "stoppedDL"
// "super_seeding" => false
// "tags" => "aniarr-1-2"
// "time_active" => 0
// "total_size" => 1737991271
// "tracker" => "http://tr.libria.fun:2710/announce"
// "trackers_count" => 2
// "up_limit" => 0
// "uploaded" => 0
// "uploaded_session" => 0
// "upspeed" => 0
