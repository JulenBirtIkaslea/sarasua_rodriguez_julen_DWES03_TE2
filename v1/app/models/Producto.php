<?php

class Producto {

    private int $id;
    private string $nombre;
    private string $categoria;
    private string $talla;
    private string $color;
    private float $precio;
    private int $stock;

    public function __construct(
        int $id,
        string $nombre,
        string $categoria,
        string $talla,
        string $color,
        float $precio,
        int $stock
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->categoria = $categoria;
        $this->talla = $talla;
        $this->color = $color;
        $this->precio = $precio;
        $this->stock = $stock;
    }

    // GETTERS
    public function getId(): int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
    public function getCategoria(): string { return $this->categoria; }
    public function getTalla(): string { return $this->talla; }
    public function getColor(): string { return $this->color; }
    public function getPrecio(): float { return $this->precio; }
    public function getStock(): int { return $this->stock; }

    // SETTERS
    public function setId(int $id): self { $this->id = $id; return $this; }
    public function setNombre(string $nombre): self { $this->nombre = $nombre; return $this; }
    public function setCategoria(string $categoria): self { $this->categoria = $categoria; return $this; }
    public function setTalla(string $talla): self { $this->talla = $talla; return $this; }
    public function setColor(string $color): self { $this->color = $color; return $this; }
    public function setPrecio(float $precio): self { $this->precio = $precio; return $this; }
    public function setStock(int $stock): self { $this->stock = $stock; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'categoria' => $this->categoria,
            'talla' => $this->talla,
            'color' => $this->color,
            'precio' => $this->precio,
            'stock' => $this->stock,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
