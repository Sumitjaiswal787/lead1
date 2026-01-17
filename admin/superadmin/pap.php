<?php
$hash = 'f35364bc808b079853de5a1e343e7159';
$candidates = ['password1','123456','letmein']; // your own guesses
foreach($candidates as $c){
    if (md5($c) === $hash) echo "Match: $c\n";
}
