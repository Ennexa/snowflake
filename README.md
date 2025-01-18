### Snowflake ID Generator

An implementation of [Snowflake](https://blog.twitter.com/engineering/en_us/a/2010/announcing-snowflake.html) ID generator that works without a dedicated daemon.

The generator can be used with `PHP-FPM` or `mod_php`.

### Installation

    composer require ennexa/snowflake

### Usage

    // First we need to create a store for saving the state
    $store = new \Ennexa\Snowflake\Store\RedisStore(new \Redis);
    // $store = new \EnnexaSnowflake\Store\FileStore('/path/to/store/state');

    // Create a generator with the created store
    $generator = new \EnnexaSnowflake\Generator($store, $instanceId = 0);

    // Use Generator::nextId to generate the next unique id
    echo $generator->nextId();


### Credits

This generator was originally created for use on [Prokerala.com](https://www.prokerala.com).
