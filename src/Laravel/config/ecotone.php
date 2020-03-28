<?php

return [
//    load app catalog namespaces automatically
    'loadAppNamespaces' => true,
//    Namespaces that should be loaded by Ecotone
    'namespaces' => [],
//    Should configuration be cached. Application will boot much faster, but will require cache:clear after changes
    'cacheConfiguration' => false,
//    What should be default serialization type e.g. application/json
    'serializationMediaType' => null,
//    What is the default error channel, where exceptions will be published
    'errorChannel' => null
];
