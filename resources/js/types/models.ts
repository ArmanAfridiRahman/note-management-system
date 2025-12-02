/**
 * Shared type definitions for the application
 */

export interface TagData {
    id: number;
    name: string;
    slug: string;
    color: string;
    notes_count?: number;
}

export interface GroupData {
    id: number;
    name: string;
    slug: string;
    color?: string;
    children?: GroupData[];
}

export interface UserData {
    id: number;
    name: string;
    email: string;
}

export interface NoteData {
    id: number;
    title: string;
    slug: string;
    content?: string;
    excerpt?: string;
    is_encrypted: boolean;
    is_pinned: boolean;
    is_archived: boolean;
    is_favorited: boolean;
    color?: string;
    group_id?: number;
    group?: GroupData;
    tags: TagData[];
    tag_ids?: number[];
    created_at: string;
    updated_at: string;
}

// For creating/editing notes where id might not exist yet
export interface NoteFormData {
    id?: number;
    title: string;
    content?: string;
    group_id?: string | number;
    tag_ids?: number[];
    is_encrypted?: boolean;
    encryption_password?: string;
    encryption_hint?: string;
    color?: string;
    is_pinned?: boolean;
}

export interface ShareData {
    id: number;
    note_id: number;
    shared_by: number;
    shared_with: number;
    permission: 'view' | 'edit';
    created_at: string;
}
