<?php 
    namespace App\Service;

    use DateTimeImmutable;

    class JWTService 
    {

        // On génère le Token 
        /**
         * Génération du JWT
         *
         * @param array $header
         * @param array $payload
         * @param string $secret
         * @param integer $validity
         * @return string
         */
        public function generate(
            array $header,
            array $payload,
            string $secret,
            int $validity = 10800
        ) : string
        {
            if($validity > 0){
                $now = new DateTimeImmutable();
                $expiration = $now->getTimestamp() + $validity;
    
                $payload["iat"] = $now->getTimestamp();
                $payload["exp"] = $expiration;
            }


            // On encode en Base64

            $base64Header = base64_encode(json_encode($header));
            $base64Payload = base64_encode(json_encode($payload));
            
            // On "nettoie" les valeurs encodées (retrait des +, / et =) qui ne sont pas utile dans JWT
            $base64Header = str_replace(["+","/","="], "", $base64Header);
            $base64Payload = str_replace(["+","/","="], "", $base64Payload);

            // On génère la signature 
            $secret = base64_encode($secret);

            $signature = hash_hmac("sha256",$base64Header.".".$base64Payload,$secret,true);

            $base64Signatue = base64_encode($signature);
            $base64Signatue = str_replace(["+","/","="], "", $base64Signatue);

            // On crée le Token 
            $jwt = $base64Header.".".$base64Payload.".".$base64Signatue;
            return $jwt;
        }
        /** Expession Régulière
         *  Encadrer par un /^$/
         *  Chaque bloc encadrer par []
         *  Chaque bloc séparé par un \.
         *  
         *  Chaque bloc peut contenir les caractères suivants 
         *  Lettre de 'a' jusqu'a z, les chiffres, les caractère - _ =
        */

        /**
         * On vérifie si le token est valide (correctement formé)
         *
         * @param string $token
         * @return boolean
         */
        public function isValid(string $token) : bool
        {
            return preg_match(
                '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
                $token

            ) === 1;
        }

        // On récupère le payload 

        public function getPayload(string $token) : array
        {
            // On démonte le Token 
            $array = explode(".",$token);

            // On décode le Payload
            $payload = json_decode(base64_decode($array[1]),true);
            
            return $payload;
        }
        // On récupère le header 

        public function getHeader(string $token) : array
        {
            // On démonte le Token 
            $array = explode(".",$token);

            // On décode le Header
            $header = json_decode(base64_decode($array[0]),true);
            
            return $header;
        }

        // On vérifie si le Token a expiré
        public function isExpiredToken(string $token) : bool
        {
            $payload = $this->getPayload($token);
            $now = new DateTimeImmutable();

            return $payload['exp'] < $now->getTimestamp();
        }

        // On vérifie la signature du Token 
        public function check(string $token, string $secret)
        {
            // On récupère le Header et le Payload 
            $header = $this->getHeader($token);
            $payload = $this->getPayload($token);

            // On regénère un Token 
            $verifToken = $this->generate($header,$payload,$secret,0);

            return $token === $verifToken;
        }
    }
?>