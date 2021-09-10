<?php

class BrowserManager {
    private $browsers;

    public function __construct() {
        $this->browsers = array();
    }

    public function push($browser) {
        $browser->connection_reuse = false;
        $this->browsers[] = $browser;
    }

    public function fetchAll($follow_redirects = true, $method = "GET") {
        foreach ($this->browsers as $browser) {
            $browser->fetch($follow_redirects, $method, true);
        }

        do {
            $finished = true;

            foreach ($this->browsers as $browser) {
                $finished &= $browser->asyncLoop();
            }
        } while (!$finished);

        return $this->browsers;
    }
}
