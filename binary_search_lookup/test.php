<?php

include "lookup.php";

$lookup = new BinarySearchLookup("test_dict", "=", "\n");

echo $lookup->findValue(str_repeat("00002344", 100))."\n";
