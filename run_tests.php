<?php

// Configurar límite de memoria
ini_set('memory_limit', '1024M');

// Verificar que se aplicó
echo "Memory limit configurado: " . ini_get('memory_limit') . "\n";

// Ejecutar tests
echo "Ejecutando tests...\n";
system('php artisan test');