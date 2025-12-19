<?php

class CsvDataHandler
{
    private string $csvFile;

    // Cabecera fija (orden consistente)
    private array $headers = ['id', 'nombre', 'categoria', 'talla', 'color', 'precio', 'stock'];

    // Separador CSV (más típico aquí)
    private string $delimiter = ';';

    public function __construct(string $csvFile)
    {
        $this->csvFile = $csvFile;

        // Si no existe, lo creamos con cabecera
        if (!file_exists($this->csvFile)) {
            $this->ensureDirectoryExists(dirname($this->csvFile));
            $this->writeHeader();
        } else {
            // Si existe pero está vacío, también metemos cabecera
            if (filesize($this->csvFile) === 0) {
                $this->writeHeader();
            } else {
                // Si no tiene cabecera válida, NO la reparamos automáticamente para no perder datos.
                // (Si quieres, se puede implementar una "migración" segura)
            }
        }
    }

    /**
     * Lee todos los productos del CSV y devuelve array de arrays asociativos.
     */
    public function readAllAsArrays(): array
    {
        $handle = fopen($this->csvFile, 'r');
        if ($handle === false) return [];

        $headers = fgetcsv($handle, 0, $this->delimiter);
        if (!is_array($headers) || count($headers) === 0) {
            fclose($handle);
            return [];
        }

        $items = [];

        while (($row = fgetcsv($handle, 0, $this->delimiter)) !== false) {
            if (count($row) === 1 && trim((string)$row[0]) === '') {
                continue; // línea vacía
            }

            // Ajuste por si alguna fila viene con columnas de menos o de más
            $row = array_slice(array_pad($row, count($headers), ''), 0, count($headers));

            $data = array_combine($headers, $row);
            if (!is_array($data)) continue;

            // Normalizamos tipos
            $items[] = $this->normalizeRow($data);
        }

        fclose($handle);
        return $items;
    }

    /**
     * Busca por ID.
     */
    public function findById(int $id): ?array
    {
        $items = $this->readAllAsArrays();
        foreach ($items as $item) {
            if ((int)($item['id'] ?? -1) === $id) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Añade un producto si el ID no existe.
     * Devuelve true si inserta, false si id duplicado/ inválido.
     */
    public function appendIfIdNotExists(array $producto): bool
    {
        $id = isset($producto['id']) ? (int)$producto['id'] : -1;
        if ($id <= 0) return false;

        if ($this->findById($id) !== null) {
            return false;
        }

        $handle = fopen($this->csvFile, 'a');
        if ($handle === false) return false;

        // Escribimos en el orden de cabecera
        $row = [];
        foreach ($this->headers as $h) {
            $row[] = $producto[$h] ?? '';
        }

        // Normalizamos
        $normalized = $this->normalizeRow(array_combine($this->headers, $row));

        // Convertimos a fila CSV (todo como string excepto id/stock, precio puede ir con punto)
        $csvRow = [
            (string)(int)$normalized['id'],
            (string)$normalized['nombre'],
            (string)$normalized['categoria'],
            (string)$normalized['talla'],
            (string)$normalized['color'],
            (string)(float)$normalized['precio'],
            (string)(int)$normalized['stock'],
        ];

        fputcsv($handle, $csvRow, $this->delimiter);
        fclose($handle);

        return true;
    }

    /**
     * Update por ID: lee todo, actualiza, reescribe.
     * $data contiene SOLO campos a actualizar (sin id).
     * Devuelve true si actualiza, false si no existe.
     */
    public function updateById(int $id, array $data): bool
    {
        $items = $this->readAllAsArrays();
        $updated = false;

        for ($i = 0; $i < count($items); $i++) {
            if ((int)($items[$i]['id'] ?? -1) === $id) {
                // No permitimos cambiar ID
                unset($data['id']);

                // Merge
                $items[$i] = array_merge($items[$i], $data);

                // Normalizar
                $items[$i] = $this->normalizeRow($items[$i]);

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
     * Delete por ID: filtra y reescribe.
     * Devuelve true si borró.
     */
    public function deleteById(int $id): bool
    {
        $items = $this->readAllAsArrays();
        $before = count($items);

        $items = array_values(array_filter(
            $items,
            fn($row) => (int)($row['id'] ?? -1) !== $id
        ));

        if (count($items) === $before) {
            return false;
        }

        $this->overwrite($items);
        return true;
    }

    /**
     * Sobrescribe el CSV completo con cabecera + filas.
     */
    private function overwrite(array $items): void
    {
        $handle = fopen($this->csvFile, 'w');
        if ($handle === false) {
            die('No se ha podido abrir el fichero CSV para sobrescritura');
        }

        // Cabecera
        fputcsv($handle, $this->headers, $this->delimiter);

        // Filas en orden de cabecera
        foreach ($items as $item) {
            $item = $this->normalizeRow($item);

            $row = [
                (string)(int)$item['id'],
                (string)$item['nombre'],
                (string)$item['categoria'],
                (string)$item['talla'],
                (string)$item['color'],
                (string)(float)$item['precio'],
                (string)(int)$item['stock'],
            ];

            fputcsv($handle, $row, $this->delimiter);
        }

        fclose($handle);
    }

    private function writeHeader(): void
    {
        $handle = fopen($this->csvFile, 'w');
        if ($handle === false) {
            die('No se ha podido crear el fichero CSV');
        }
        fputcsv($handle, $this->headers, $this->delimiter);
        fclose($handle);
    }

    private function normalizeRow(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int)$row['id'] : 0;
        $row['nombre'] = (string)($row['nombre'] ?? '');
        $row['categoria'] = (string)($row['categoria'] ?? '');
        $row['talla'] = (string)($row['talla'] ?? '');
        $row['color'] = (string)($row['color'] ?? '');
        $row['precio'] = isset($row['precio']) ? (float)$row['precio'] : 0.0;
        $row['stock'] = isset($row['stock']) ? (int)$row['stock'] : 0;

        return $row;
    }

    private function ensureDirectoryExists(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}
