<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Modelo: Agenda (eventos / citas)
 *
 * Un evento tiene fecha obligatoria y hora opcional. Si todo_el_dia
 * es 1, se omite la hora al renderizar.
 * fecha_fin es opcional y permite representar eventos de varios días.
 */
final class Agenda
{
    public const COLORES = ['rosa', 'crema', 'menta', 'chocolate'];

    public static function all(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT a.*, c.emoji AS cat_emoji, c.nombre AS cat_nombre
               FROM agenda a
          LEFT JOIN categorias c ON c.id = a.categoria_id
              ORDER BY a.fecha ASC, a.hora ASC, a.id ASC'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT a.*, c.emoji AS cat_emoji, c.nombre AS cat_nombre
               FROM agenda a
          LEFT JOIN categorias c ON c.id = a.categoria_id
              WHERE a.id = ?',
            [$id]
        );
    }

    /** Devuelve los eventos dentro de un rango (FullCalendar usa start/end ISO). */
    public static function between(string $start, string $end): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT a.*, c.emoji AS cat_emoji, c.nombre AS cat_nombre
               FROM agenda a
          LEFT JOIN categorias c ON c.id = a.categoria_id
              WHERE a.fecha <= ? AND (a.fecha_fin IS NULL OR a.fecha_fin >= ?)
              ORDER BY a.fecha ASC, a.hora ASC',
            [$end, $start]
        );
    }

    public static function create(array $data): int
    {
        self::validate($data);
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO agenda (titulo, descripcion, fecha, fecha_fin, hora, todo_el_dia, color, categoria_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                trim($data['titulo']),
                isset($data['descripcion']) && $data['descripcion'] !== '' ? trim($data['descripcion']) : null,
                $data['fecha'],
                self::normalizarFechaFin($data['fecha_fin'] ?? null, $data['fecha']),
                self::normalizarHora($data['hora'] ?? null, !empty($data['todo_el_dia'])),
                !empty($data['todo_el_dia']) ? 1 : 0,
                self::normalizarColor($data['color'] ?? 'rosa'),
                self::normalizarCategoria($data['categoria_id'] ?? null),
            ]
        );
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        self::validate($data);
        $affected = Database::getInstance()->execute(
            'UPDATE agenda
                SET titulo = ?, descripcion = ?, fecha = ?, fecha_fin = ?,
                    hora = ?, todo_el_dia = ?, color = ?, categoria_id = ?,
                    updated_at = CURRENT_TIMESTAMP
              WHERE id = ?',
            [
                trim($data['titulo']),
                isset($data['descripcion']) && $data['descripcion'] !== '' ? trim($data['descripcion']) : null,
                $data['fecha'],
                self::normalizarFechaFin($data['fecha_fin'] ?? null, $data['fecha']),
                self::normalizarHora($data['hora'] ?? null, !empty($data['todo_el_dia'])),
                !empty($data['todo_el_dia']) ? 1 : 0,
                self::normalizarColor($data['color'] ?? 'rosa'),
                self::normalizarCategoria($data['categoria_id'] ?? null),
                $id,
            ]
        )->rowCount();
        return $affected > 0;
    }

    public static function delete(int $id): bool
    {
        $affected = Database::getInstance()->execute(
            'DELETE FROM agenda WHERE id = ?',
            [$id]
        )->rowCount();
        return $affected > 0;
    }

    private static function validate(array $data): void
    {
        if (empty($data['titulo']) || mb_strlen(trim($data['titulo'])) < 1) {
            throw new \InvalidArgumentException('El título del evento es obligatorio.');
        }
        if (mb_strlen(trim($data['titulo'])) > 180) {
            throw new \InvalidArgumentException('El título es demasiado largo (máx 180 caracteres).');
        }
        if (empty($data['fecha'])) {
            throw new \InvalidArgumentException('La fecha es obligatoria.');
        }
        $d = \DateTime::createFromFormat('Y-m-d', $data['fecha']);
        if (!$d || $d->format('Y-m-d') !== $data['fecha']) {
            throw new \InvalidArgumentException('Fecha inválida.');
        }
        if (!empty($data['fecha_fin'])) {
            $df = \DateTime::createFromFormat('Y-m-d', $data['fecha_fin']);
            if (!$df || $df->format('Y-m-d') !== $data['fecha_fin']) {
                throw new \InvalidArgumentException('Fecha de fin inválida.');
            }
            if ($df < $d) {
                throw new \InvalidArgumentException('La fecha de fin no puede ser anterior a la fecha de inicio.');
            }
        }
        if (!empty($data['hora'])) {
            $h = \DateTime::createFromFormat('H:i', $data['hora']);
            if (!$h || $h->format('H:i') !== $data['hora']) {
                throw new \InvalidArgumentException('Hora inválida.');
            }
        }
    }

    private static function normalizarFechaFin(?string $fin, string $inicio): ?string
    {
        if (!$fin || trim($fin) === '') return null;
        if ($fin < $inicio) return null;   // validate() ya lanzó error, esto es defensa
        return $fin;
    }

    private static function normalizarHora(?string $hora, bool $todoElDia): ?string
    {
        if ($todoElDia) return null;
        if (!$hora || trim($hora) === '') return null;
        return $hora;
    }

    private static function normalizarColor(string $color): string
    {
        return in_array($color, self::COLORES, true) ? $color : 'rosa';
    }

    private static function normalizarCategoria($id): ?int
    {
        if ($id === null || $id === '' || $id === '0') return null;
        $id = (int)$id;
        return $id > 0 ? $id : null;
    }

    /** Convierte un evento al formato esperado por FullCalendar. */
    public static function toCalendarEvent(array $e): array
    {
        $colorMap = [
            'rosa'       => '#ff6b9d',
            'crema'      => '#ffb37a',
            'menta'      => '#3eb97a',
            'chocolate'  => '#8b4513',
        ];
        $c = $colorMap[$e['color']] ?? '#ff6b9d';

        $tieneFin = !empty($e['fecha_fin']) && $e['fecha_fin'] !== $e['fecha'];

        if (!empty($e['todo_el_dia'])) {
            // Para eventos de todo el día en FullCalendar, end es exclusivo (+1 día)
            $end = $tieneFin ? self::sumarUnDia($e['fecha_fin']) : null;
            $ev = [
                'id'    => 'ag_' . (int)$e['id'],
                'title' => $e['titulo'],
                'start' => $e['fecha'],
                'allDay'=> true,
                'backgroundColor' => $c,
                'borderColor'     => $c,
                'extendedProps'   => [
                    'db_id'       => (int)$e['id'],
                    'descripcion' => $e['descripcion'] ?? '',
                    'color'       => $e['color'],
                    'fecha_fin'   => $e['fecha_fin'] ?? null,
                    'todo_el_dia' => true,
                ],
            ];
            if ($end) $ev['end'] = $end;
            return $ev;
        }

        $ev = [
            'id'    => 'ag_' . (int)$e['id'],
            'title' => $e['titulo'],
            'start' => $e['fecha'] . 'T' . ($e['hora'] ?: '00:00'),
            'allDay'=> false,
            'backgroundColor' => $c,
            'borderColor'     => $c,
            'extendedProps'   => [
                'db_id'       => (int)$e['id'],
                'descripcion' => $e['descripcion'] ?? '',
                'color'       => $e['color'],
                'fecha_fin'   => $e['fecha_fin'] ?? null,
                'todo_el_dia' => false,
            ],
        ];
        if ($tieneFin) {
            $ev['end'] = $e['fecha_fin'] . 'T' . ($e['hora'] ?: '23:59');
        }
        return $ev;
    }

    private static function sumarUnDia(string $fecha): string
    {
        $d = new \DateTime($fecha);
        $d->modify('+1 day');
        return $d->format('Y-m-d');
    }
}