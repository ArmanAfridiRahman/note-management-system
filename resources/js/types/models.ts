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
    avatar?: string;
    avatar_url: string;
    color?: string;
    display_color: string;
    initials: string;
}

export interface NoteShareData {
    id: number;
    note_id: number;
    shared_by_user_id: number;
    shared_with_user_id: number;
    permission: 'view' | 'edit';
    shared_with_user?: UserData;
    created_at: string;
}

// Share metadata for "Shared With Me" notes
export interface SharedWithMeData {
    id: number;
    permission: 'view' | 'edit';
    expires_at?: string;
    message?: string;
    shared_by: UserData;
    created_at: string;
}

// Share recipient for "Shared By Me" notes
export interface ShareRecipientData {
    id: number;
    user: UserData;
    permission: 'view' | 'edit';
    expires_at?: string;
    message?: string;
    created_at: string;
}

export interface ParentSnapshot {
    title: string;
    content?: string;
    excerpt?: string;
    color?: string;
    replicated_at: string;
}

export interface NoteMetaData {
    parent_snapshot?: ParentSnapshot;
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
    open_count?: number;
    color?: string;
    group_id?: number;
    parent_id?: number;
    group?: GroupData;
    parent?: NoteData;
    children?: NoteData[];
    meta_data?: NoteMetaData;
    tags: TagData[];
    tag_ids?: number[];
    shares?: NoteShareData[];
    encryption_hint?: string;
    archived_at?: string;
    created_at: string;
    updated_at: string;
    // Share context fields (added by API for shared pages)
    share?: SharedWithMeData;
    is_shared_with_me?: boolean;
    share_recipients?: ShareRecipientData[];
    is_shared_by_me?: boolean;
    user?: UserData; // Note owner (for shared with me)
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
