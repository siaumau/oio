<template>
  <div class="rich-text-editor">
    <div class="editor-toolbar">
      <button
        type="button"
        @click="editor?.chain().focus().toggleBold().run()"
        :class="{ active: editor?.isActive('bold') }"
        title="粗體 (Ctrl+B)"
        class="toolbar-btn"
      >
        <strong>B</strong>
      </button>

      <button
        type="button"
        @click="editor?.chain().focus().toggleItalic().run()"
        :class="{ active: editor?.isActive('italic') }"
        title="斜體 (Ctrl+I)"
        class="toolbar-btn"
      >
        <em>I</em>
      </button>

      <div class="toolbar-separator"></div>

      <button
        type="button"
        @click="editor?.chain().focus().toggleBulletList().run()"
        :class="{ active: editor?.isActive('bulletList') }"
        title="項目符號列表"
        class="toolbar-btn"
      >
        • 列表
      </button>

      <div class="toolbar-separator"></div>

      <button
        type="button"
        @click="$refs.imageInput?.click()"
        title="插入圖片"
        class="toolbar-btn"
      >
        📎 上傳圖片
      </button>

      <input
        ref="imageInput"
        type="file"
        accept="image/*"
        hidden
        @change="handleImageSelect"
      />

      <button
        type="button"
        @click="editor?.chain().focus().clearContent().run()"
        title="清空內容"
        class="toolbar-btn"
      >
        🗑️ 清空
      </button>
    </div>

    <div
      class="editor-content"
      @paste.prevent="handlePaste"
      @dragover.prevent
      @drop.prevent="handleDrop"
    >
      <EditorContent :editor="editor" />
      <!-- 圖片刪除按鈕 (浮動) -->
      <div
        v-if="hoveredImageElement"
        class="image-delete-button"
        :style="{
          top: `${imageDeleteButtonPos.top}px`,
          left: `${imageDeleteButtonPos.left}px`
        }"
        @click="deleteHoveredImage"
        @mouseenter="handleDeleteButtonEnter"
        @mouseleave="handleDeleteButtonLeave"
        title="刪除圖片"
      >
        ✕
      </div>
    </div>

    <div v-if="uploadingImage" class="uploading-hint">
      上傳中...
    </div>
  </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import { ref, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  taskId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['update:modelValue'])

const imageInput = ref(null)
const uploadingImage = ref(false)
const hoveredImageElement = ref(null)
const imageDeleteButtonPos = ref({ top: 0, left: 0 })

const editor = useEditor({
  extensions: [
    StarterKit,
    Image.configure({
      allowBase64: false,
      HTMLAttributes: {
        class: 'editor-image'
      }
    })
  ],
  content: props.modelValue,
  onCreate: ({ editor }) => {
    // 編輯器初始化完成後綁定圖片事件
    nextTick(() => {
      attachImageEventListeners()
    })
  },
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
    // 重新綁定圖片事件監聽器
    nextTick(() => {
      attachImageEventListeners()
    })
  }
})

// 監聽外部 modelValue 變化
watch(
  () => props.modelValue,
  (newValue) => {
    if (editor.value && editor.value.getHTML() !== newValue) {
      editor.value.commands.setContent(newValue)
      nextTick(() => {
        attachImageEventListeners()
      })
    }
  }
)

// 為圖片綁定 hover 事件
const attachImageEventListeners = () => {
  const images = document.querySelectorAll('.editor-image')
  images.forEach((img) => {
    // 移除舊的事件監聽器（避免重複添加）
    img.removeEventListener('mouseenter', handleImageHover)
    img.removeEventListener('mouseleave', handleImageLeave)
    // 添加新的事件監聽器
    img.addEventListener('mouseenter', handleImageHover)
    img.addEventListener('mouseleave', handleImageLeave)
  })
}

// 圖片 hover 事件
const handleImageHover = (event) => {
  const img = event.target
  hoveredImageElement.value = img

  // 計算刪除按鈕位置 (圖片右上角)
  const rect = img.getBoundingClientRect()
  const editorContent = document.querySelector('.editor-content')
  const editorRect = editorContent.getBoundingClientRect()

  // 考慮編輯器內的滾動偏移
  const scrollOffset = editorContent.scrollTop

  imageDeleteButtonPos.value = {
    top: rect.top - editorRect.top + scrollOffset - 16,  // 按鈕高度的一半，放在圖片頂部邊緣
    left: rect.right - editorRect.left - 24  // 按鈕寬度 - 邊距
  }
}

// 圖片 leave 事件
const handleImageLeave = () => {
  // 延遲隱藏，以便點擊刪除按鈕
  setTimeout(() => {
    // 檢查鼠標是否在刪除按鈕上
    const deleteBtn = document.querySelector('.image-delete-button')
    if (deleteBtn && deleteBtn.matches(':hover')) {
      // 鼠標在按鈕上，不隱藏
      return
    }
    hoveredImageElement.value = null
  }, 100)
}

// 刪除 hover 的圖片
const deleteHoveredImage = () => {
  if (!hoveredImageElement.value || !editor.value) return

  // 找到圖片對應的節點位置
  let pos = 0
  editor.value.state.doc.descendants((node) => {
    if (node.type.name === 'image' && node.attrs.src === hoveredImageElement.value.src) {
      editor.value.chain().setNodeSelection(pos).deleteSelection().run()
      hoveredImageElement.value = null
      return false
    }
    pos += node.nodeSize
  })
}

// 刪除按鈕 hover 事件
const handleDeleteButtonEnter = () => {
  // 保持顯示，不隱藏
}

const handleDeleteButtonLeave = () => {
  // 延遲隱藏，給用戶時間點擊
  setTimeout(() => {
    hoveredImageElement.value = null
  }, 100)
}

const handleImageSelect = async (event) => {
  const files = event.target.files
  if (!files) return

  for (const file of files) {
    if (file.type.startsWith('image/')) {
      await insertImage(file)
    }
  }

  // 重置 file input
  event.target.value = ''
}

const handlePaste = async (event) => {
  const items = event.clipboardData?.items
  if (!items) return

  for (const item of items) {
    if (item.type.startsWith('image/')) {
      event.preventDefault()
      const file = item.getAsFile()
      if (file) {
        await insertImage(file)
      }
    }
  }
}

const handleDrop = async (event) => {
  const files = event.dataTransfer?.files
  if (!files) return

  for (const file of files) {
    if (file.type.startsWith('image/')) {
      await insertImage(file)
    }
  }
}

const insertImage = async (file) => {
  if (!editor.value) return

  // 將圖片轉換為 base64 並插入到編輯器中
  const reader = new FileReader()
  reader.onload = (e) => {
    const imageUrl = e.target.result
    editor.value.chain().focus().setImage({ src: imageUrl }).run()
  }
  reader.readAsDataURL(file)

  // 以下代碼已廢棄，改為後端處理
  /*
  uploadingImage.value = true

  try {
    // 上傳圖片到服務器
    const formData = new FormData()
    formData.append('image', file)

    const response = await fetch(
      `http://localhost:6001/api/tasks/${props.taskId}?action=addImage&source=description`,
      {
        method: 'POST',
        credentials: 'include',
        body: formData
      }
    )

    const data = await response.json()

    if (data.success) {
      const imageUrl = `http://localhost:6001/${data.data.file_path}`
      editor.value.chain().focus().setImage({ src: imageUrl }).run()
    } else {
      alert('圖片上傳失敗：' + data.message)
    }
  } catch (err) {
    console.error('Error uploading image:', err)
    alert('圖片上傳出錯，請檢查網絡連接')
  } finally {
    uploadingImage.value = false
  }
  */
}

</script>

<style scoped>
.rich-text-editor {
  border: 1px solid var(--color-gray-300);
  border-radius: var(--radius-md);
  overflow: hidden;
  background-color: white;
}

.editor-toolbar {
  display: flex;
  gap: 4px;
  padding: 8px;
  background-color: var(--color-gray-50);
  border-bottom: 1px solid var(--color-gray-300);
  align-items: center;
  flex-wrap: wrap;
}

.toolbar-btn {
  padding: 6px 12px;
  border: 1px solid var(--color-gray-300);
  background-color: white;
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
  transition: all 0.2s ease;
  min-width: 36px;
  text-align: center;
}

.toolbar-btn:hover {
  background-color: var(--color-gray-100);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.toolbar-btn.active {
  background-color: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.toolbar-separator {
  width: 1px;
  height: 24px;
  background-color: var(--color-gray-300);
  margin: 0 4px;
}

.editor-content {
  min-height: 200px;
  padding: 12px;
  overflow-y: auto;
  max-height: 400px;
  position: relative;
}

.editor-content :deep(.ProseMirror) {
  outline: none;
  font-size: 14px;
  line-height: 1.6;
}

.editor-content :deep(.ProseMirror p) {
  margin: 8px 0;
}

.editor-content :deep(.ProseMirror ul) {
  margin: 8px 0;
  padding-left: 24px;
}

.editor-content :deep(.ProseMirror li) {
  margin: 4px 0;
}

.editor-content :deep(.editor-image) {
  max-width: 100%;
  height: auto;
  border-radius: var(--radius-md);
  margin: 8px 0;
  box-shadow: var(--shadow-sm);
  cursor: pointer;
  position: relative;
  transition: all 0.2s ease;
}

.editor-content :deep(.editor-image:hover) {
  box-shadow: var(--shadow-md);
  opacity: 0.9;
  outline: 2px solid var(--color-primary);
  outline-offset: -2px;
}

.image-delete-button {
  position: absolute;
  width: 32px;
  height: 32px;
  background-color: #ff4444;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
  cursor: pointer;
  box-shadow: var(--shadow-md);
  transition: all 0.2s ease;
  z-index: 100;
  pointer-events: auto;
  border: none;
  font-weight: bold;
  line-height: 1;
}

.image-delete-button:hover {
  background-color: #ff2222;
  transform: scale(1.1);
  box-shadow: var(--shadow-lg);
}

.uploading-hint {
  padding: 8px 12px;
  background-color: rgba(0, 102, 204, 0.08);
  color: var(--color-primary);
  font-size: 12px;
  text-align: center;
  border-top: 1px solid var(--color-gray-200);
}
</style>
