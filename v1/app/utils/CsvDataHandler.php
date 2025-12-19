<?php

class CsvDataHandler
{
    private string $csvFile;

    // Cabecera fija (orden consistente)
    private array $headers = ['id', 'nombre', 'categoria', 'talla', 'color', 'precio', 'stock'];

    private string $delimiter = ';';

    public function __construct(string $csvFile)
    {
        $this->csvFile = $csvFile;

        if (!file_exists($this->csvFile)) {
            $this->ensureDirectoryExists(dirname($this->csvFile));
            $this->writeHeader();
        } else {
            if (filesize($this->csvFile) === 0) {
                $this->writeHeader();
            }
        }
    }

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
                continue;
            }

            $row = array_slice(array_pad($row, count($headers), ''), 0, count($headers));

            $data = array_combine($headers, $row);
            if (!is_array($data)) continue;

            $items[] = $this->normalizeRow($data);
        }

        fclose($handle);
        return $items;
    }

    public function findById(int $id): ?array
    {
        foreach ($this->readAllAsArrays() as $item) {
            if ((int)($item['id'] ?? -1) === $id) return $item;
        }
        return null;
    }

    public function appendIfIdNotExists(array $producto): bool
    {
        $id = isset($producto['id']) ? (int)$producto['id'] : -1;
        if ($id <= 0) return false;

        if ($this->findById($id) !== null) return false;

        $handle = fopen($this->csvFile, 'a');
        if ($handle === false) return false;

        // Completar con cabecera fija
        $rowAssoc = [];
        foreach ($this->headers as $h) {
            $rowAssoc[$h] = $producto[$h] ?? '';
        }

        $rowAssoc = $this->normalizeRow($rowAssoc);

        $csvRow = [
            (string)(int)$rowAssoc['id'],
            (string)$rowAssoc['nombre'],
            (string)$rowAssoc['categoria'],
            (string)$rowAssoc['talla'],
            (string)$rowAssoc['color'],
            (string)(float)$rowAssoc['precio'],
            (string)(int)$rowAssoc['stock'],
        ];

        fputcsv($handle, $csvRow, $this->delimiter);
        fclose($handle);

        return true;
    }

    public function updateById(int $id, array $data): bool
    {
        $items = $this->readAllAsArrays();
        $updated = false;

        for ($i = 0; $i < count($items); $i++) {
            if ((int)($items[$i]['id'] ?? -1) === $id) {
                unset($data['id']); // no permitir cambiar id
                $items[$i] = $this->normalizeRow(array_merge($items[$i], $data));
                $updated = true;
                break;
            }
        }

        if ($updated) $this->overwrite($items);

        return $updated;
    }

    public function deleteById(int $id): bool
    {
        $items = $this->readAllAsArrays();
        $before = count($items);

        $items = array_values(array_filter(
            $items,
            fn($row) => (int)($row['id'] ?? -1) !== $id
        ));

        if (count($items) === $before) return false;

        $this->overwrite($items);
        return true;
    }

    private function overwrite(array $items): void
    {
        $handle = fopen($this->csvFile, 'w');
        if ($handle === false) {
            die('No se ha podido abrir el fichero CSV para sobrescritura');
        }

        fputcsv($handle, $this->headers, $this->delimiter);

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
