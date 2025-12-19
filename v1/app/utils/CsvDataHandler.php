<?php

require_once __DIR__ . '/../models/Producto.php';

class CsvDataHandler {

    private string $csvFile;

    public function __construct(string $csvFile)
    {
        $this->csvFile = $csvFile;

        // Si no existe, lo creamos vacío (sin cabecera)
        if (!file_exists($this->csvFile)) {
            $handle = fopen($this->csvFile, 'w');
            if ($handle === false) {
                die('No se ha podido crear el fichero CSV');
            }
            fclose($handle);
        }
    }

    /**
     * Lee el fichero completo y devuelve array de arrays asociativos (cada línea un JSON)
     */
    public function readAllAsArrays(): array
    {
        $lines = file($this->csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) return [];

        $items = [];

        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if (is_array($data)) {
                $items[] = $data;
            }
        }

        return $items;
    }

    /**
     * Busca por ID leyendo todo el CSV y comparando cada JSON
     */
    public function findById(int $id): ?array
    {
        $items = $this->readAllAsArrays();

        foreach ($items as $data) {
            if (isset($data['id']) && (int)$data['id'] === $id) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Añade un nuevo item al CSV (1 JSON por línea) SOLO si no existe el id
     */
    public function appendIfIdNotExists(array $data): bool
    {
        if (!isset($data['id'])) {
            return false;
        }

        $id = (int)$data['id'];

        if ($this->findById($id) !== null) {
            return false; // id duplicado
        }

        file_put_contents(
            $this->csvFile,
            json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND
        );

        return true;
    }

    /**
     * Sobrescribe el fichero completo con array de arrays (cada uno JSON por línea)
     */
    public function overwrite(array $items): void
    {
        $content = '';
        foreach ($items as $data) {
            $content .= json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
        file_put_contents($this->csvFile, $content);
    }

    /**
     * Update por ID: lee todo, foreach, modifica el que coincide, reescribe todo.
     * Devuelve true si actualizó.
     */
    public function updateById(int $id, array $newData): bool
    {
        $items = $this->readAllAsArrays();
        $updated = false;

        foreach ($items as &$data) {
            if (isset($data['id']) && (int)$data['id'] === $id) {

                // ID no se cambia: manda $id
                $data['id'] = $id;

                // Actualiza solo lo que venga, excepto id
                foreach ($newData as $k => $v) {
                    if ($k === 'id') continue;
                    $data[$k] = $v;
                }

                $updated = true;
                break;
            }
        }

        if ($updated) {
            $this->overwrite($items);
        }

        return $updated;
    }

    /**
     * Delete por ID: lee todo, filtra, reescribe.
     * Devuelve true si borró.
     */
    public function deleteById(int $id): bool
    {
        $items = $this->readAllAsArrays();
        $before = count($items);

        $items = array_values(array_filter(
            $items,
            fn($data) => (int)($data['id'] ?? -1) !== $id
        ));

        if (count($items) === $before) {
            return false;
        }

        $this->overwrite($items);
        return true;
    }
}
