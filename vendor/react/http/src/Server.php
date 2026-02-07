<?php

namespace React\Http;


\class_alias(__NAMESPACE__ . '\\HttpServer', __NAMESPACE__ . '\\Server', true);



if (!\class_exists(__NAMESPACE__ . '\\Server', false)) {
    
    final class Server extends HttpServer
    {
    }
}
