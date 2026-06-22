<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Core\Model;

class Message extends Model
{
    protected string $table = 'messages';

    /**
     * Fetches the latest messages for a specific room, including user and reply details.
     * This method is assumed to exist based on ChatController usage.
     *
     * @param int $roomId
     * @param int $limit
     * @return array
     */
    public function latestForRoom(int $roomId, int $limit = 50): array
    {
        // Placeholder implementation - replace with actual logic from your Message model
        // This should fetch messages for a given room, join with users for name/avatar,
        // and potentially with other messages for reply details.
        $sql = "
            SELECT
                m.id,
                m.room_id,
                m.user_id,
                m.body,
                m.attachment_path,
                m.attachment_type,
                m.attachment_name,
                m.created_at,
                m.updated_at,
                m.deleted_at,
                m.is_pinned,
                u.name,
                u.avatar_path,
                r.name AS room_name,
                r.slug AS room_slug,
                (SELECT COUNT(*) FROM message_reactions WHERE message_id = m.id AND reaction = 'like') AS reactions_like,
                (SELECT COUNT(*) FROM messages WHERE reply_to_id = m.id) AS reply_count,
                reply_m.body AS reply_body,
                reply_u.name AS reply_name
            FROM
                {$this->table} m
            JOIN
                users u ON m.user_id = u.id
            JOIN
                rooms r ON m.room_id = r.id
            LEFT JOIN
                messages reply_m ON m.reply_to_id = reply_m.id
            LEFT JOIN
                users reply_u ON reply_m.user_id = reply_u.id
            WHERE
                m.room_id = :room_id AND m.deleted_at IS NULL
            ORDER BY
                m.created_at DESC
            LIMIT :limit
        ";

        $messages = $this->db->query($sql, [
            ':room_id' => $roomId,
            ':limit' => $limit,
        ])->fetchAll(\PDO::FETCH_ASSOC);

        return array_reverse($messages); // Assuming frontend expects oldest first for room chat
    }

    /**
     * Fetches the latest public messages from all public rooms, including user and room details.
     * This is for the global feed.
     *
     * @param int $limit
     * @return array
     */
    public function allPublicLatest(int $limit = 50): array
    {
        $sql = "
            SELECT
                m.id, m.room_id, m.user_id, m.body, m.attachment_path, m.attachment_type, m.attachment_name,
                m.created_at, m.updated_at, m.deleted_at, m.is_pinned,
                u.name, u.avatar_path,
                r.name AS room_name, r.slug AS room_slug,
                (SELECT COUNT(*) FROM message_reactions WHERE message_id = m.id AND reaction = 'like') AS reactions_like,
                (SELECT COUNT(*) FROM messages WHERE reply_to_id = m.id) AS reply_count,
                reply_m.body AS reply_body, reply_u.name AS reply_name
            FROM {$this->table} m
            JOIN users u ON m.user_id = u.id
            JOIN rooms r ON m.room_id = r.id
            LEFT JOIN messages reply_m ON m.reply_to_id = reply_m.id
            LEFT JOIN users reply_u ON reply_m.user_id = reply_u.id
            WHERE r.scope = 'public' AND m.deleted_at IS NULL
            ORDER BY m.created_at DESC
            LIMIT :limit
        ";

        return $this->db->query($sql, [':limit' => $limit])->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Add other methods like create, findInRoom, deleteIfAllowed, toggleReaction,
    // togglePin, markTyping, recentForAdmin, deleteAsAdmin as needed based on your application.
}
