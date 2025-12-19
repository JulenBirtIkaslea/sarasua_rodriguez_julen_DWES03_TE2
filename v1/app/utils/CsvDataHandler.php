<?php

class CsvDataHandler
{
    // Ruta al fichero CSV donde se guardan los datos
    private string $csvFile;

    // Separador del CSV (usamos ; como en clase)
    private string $delimiter = ';';

    /**
     * Constructor
     * Recibe la ruta del fichero CSV y se asegura de que existe
     * Si el fichero no existe o está vacío, crea la cabecera
     */
    public function __construct(string $csvFile)
    {
        $this->csvFile = $csvFile;

        // Si el fichero no existe o está vacío, lo inicializamos
        if (!file_exists($this->csvFile) || filesize($this->csvFile) === 0) {

            // Creamos la carpeta si no existe
            $dir = dirname($this->csvFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            // Creamos el fichero y escribimos la cabecera
            $h = fopen($this->csvFile, 'w');

            // Cabecera mínima del CSV (columnas del producto)
            fputcsv(
                $h,
                ['id', 'nombre', 'categoria', 'talla', 'color', 'precio', 'stock'],
                $this->delimiter
            );

            fclose($h);
        }
    }

    /**
     * Lee todo el contenido del CSV y lo devuelve como array de arrays
     */
    public function readAllAsArrays(): array
    {
        $h = fopen($this->csvFile, 'r');
        if (!$h) return [];

        // Leemos la cabecera para usarla como claves
        $headers = fgetcsv($h, 0, $this->delimiter);
        if (!$headers) {
            fclose($h);
            return [];
        }

        $items = [];

        // Leemos cada fila y la convertimos en array asociativo
        while (($row = fgetcsv($h, 0, $this->delimiter)) !== false) {
            $items[] = array_combine($headers, $row);
        }

        fclose($h);
        return $items;
    }

    /**
     * Busca un elemento por su ID
     * Devuelve el array si existe o null si no
     */
    public function findById(int $id): ?array
    {
        foreach ($this->readAllAsArrays() as $item) {
            if ((int)$item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Añade un producto al CSV si el ID no existe
     * Devuelve true si se inserta, false si hay error o ID duplicado
     */
    public function appendIfIdNotExists(array $producto): bool
    {
        $id = isset($producto['id']) ? (int)$producto['id'] : 0;
        if ($id <= 0) return false;

        // Comprobamos que el ID no esté ya en el CSV
        if ($this->findById($id) !== null) return false;

        // Preparamos la fila asegurando todas las columnas
        $row = [
            $id,
            $producto['nombre'] ?? '',
            $producto['categoria'] ?? '',
            $producto['talla'] ?? '',
            $producto['color'] ?? '',
            $producto['precio'] ?? '',
            $producto['stock'] ?? ''
        ];

        // Abrimos el fichero en modo añadir
        $h = fopen($this->csvFile, 'a');
        if (!$h) return false;

        fputcsv($h, $row, $this->delimiter);
        fclose($h);

        return true;
    }

    /**
     * Actualiza un producto por ID
     * Devuelve false si el ID no existe
     */
    public function updateById(int $id, array $data): bool
    {
        // Leemos todos los elementos
        $items = $this->readAllAsArrays();
        $found = false;

        // Buscamos el producto por ID
        for ($i = 0; $i < count($items); $i++) {
            if ((int)$items[$i]['id'] === $id) {

                // No permitimos cambiar el ID
                unset($data['id']);

                // Actualizamos solo los campos enviados
                $items[$i] = array_merge($items[$i], $data);
                $found = true;
                break;
            }
        }

        // Si no se encuentra el ID, no se actualiza nada
        if (!$found) return false;

        // Reescribimos el CSV completo
        $this->overwrite($items);
        return true;
    }

    /**
     * Borra un producto por ID
     * Devuelve false si el ID no existe
     */
    public function deleteById(int $id): bool
    {
        $items = $this->readAllAsArrays();
        $before = count($items);

        // Eliminamos el elemento con ese ID
        $items = array_values(
            array_filter($items, fn($row) => (int)$row['id'] !== $id)
        );

        // Si no cambia el número de elementos, el ID no existía
        if (count($items) === $before) return false;

        // Reescribimos el CSV
        $this->overwrite($items);
        return true;
    }

    /**
     * Sobrescribe el CSV completo (cabecera + datos)
     * Se usa en update y delete
     */
    private function overwrite(array $items): void
    {
        $h = fopen($this->csvFile, 'w');
        if (!$h) return;

        // Escribimos la cabecera
        fputcsv(
            $h,
            ['id', 'nombre', 'categoria', 'talla', 'color', 'precio', 'stock'],
            $this->delimiter
        );

        // Escribimos cada fila
        foreach ($items as $item) {
            $row = [
                $item['id'] ?? '',
                $item['nombre'] ?? '',
                $item['categoria'] ?? '',
                $item['talla'] ?? '',
                $item['color'] ?? '',
                $item['precio'] ?? '',
                $item['stock'] ?? ''
            ];
            fputcsv($h, $row, $this->delimiter);
        }

        fclose($h);
    }
}
