<template>
  <div class="exception-debugger p-4">
    <!-- Main Exception -->
    <div v-if="exception" class="exception-block mb-6">
      <div class="exception-header bg-red-50 dark:bg-red-900/20 p-4 rounded-t-lg border-l-4 border-red-500">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <h3 class="text-lg font-semibold text-red-800 dark:text-red-300">
              {{ exception.class }}
            </h3>
            <p class="text-sm text-red-600 dark:text-red-400 mt-1">
              {{ exception.message }}
            </p>
          </div>
        </div>

        <div class="mt-2 text-xs text-red-700 dark:text-red-300 font-mono" v-if="exception.file">
          {{ exception.relative_file || exception.file }}:<span class="font-bold">{{ exception.line }}</span>
        </div>
      </div>

      <!-- Code Snippet -->
      <div v-if="exception.snippet && exception.snippet.length" class="code-snippet bg-gray-900 text-gray-100 p-4 overflow-x-auto">
        <table class="w-full font-mono text-sm">
          <tbody>
            <tr v-for="snippetLine in exception.snippet" :key="snippetLine.line"
                :class="[snippetLine.highlighted ? 'bg-red-900/40' : '', 'hover:bg-gray-800']">
              <td class="text-right pr-4 text-gray-500 select-none w-12">
                {{ snippetLine.line }}
              </td>
              <td class="pl-4">
                <pre class="m-0 whitespace-pre-wrap" :class="[snippetLine.highlighted ? 'highlighted-line' : '']"><code class="language-php">{{ snippetLine.code }}</code></pre>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Stack Trace -->
      <div v-if="exception.frames && exception.frames.length" class="stacktrace mt-4">
        <button
          @click="showStackTrace = !showStackTrace"
          class="w-full text-left px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded flex items-center justify-between"
        >
          <span class="font-semibold text-gray-700 dark:text-gray-300">
            Stack Trace ({{ exception.frames.length }} frames)
          </span>
          <ChevronRightIcon :class="[showStackTrace ? 'rotate-90' : '', 'w-5 h-5 transition-transform']" />
        </button>

        <div v-show="showStackTrace" class="mt-2 space-y-2">
          <div v-for="(frame, idx) in exception.frames" :key="idx"
               class="frame border dark:border-gray-700 rounded overflow-hidden">
            <div @click="toggleFrame(idx)"
                 :class="[
                   frame.is_vendor ? 'bg-gray-50 dark:bg-gray-800/50' : 'bg-white dark:bg-gray-800',
                   'p-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'
                 ]">
              <div class="flex items-start justify-between">
                <div class="flex-1 font-mono text-xs">
                  <div class="text-gray-900 dark:text-gray-100">
                    <span v-if="frame.class" class="text-blue-600 dark:text-blue-400">{{ frame.class }}</span>
                    <span v-if="frame.type" class="text-gray-500">{{ frame.type }}</span>
                    <span class="text-purple-600 dark:text-purple-400">{{ frame.function }}</span>
                  </div>
                  <div class="text-gray-600 dark:text-gray-400 mt-1" v-if="frame.file">
                    {{ frame.relative_file || frame.file }}:<span class="font-bold">{{ frame.line }}</span>
                  </div>
                </div>
                <ChevronRightIcon v-if="frame.snippet"
                  :class="[openFrames.includes(idx) ? 'rotate-90' : '', 'w-4 h-4 transition-transform text-gray-400']" />
              </div>
            </div>

            <!-- Frame Code Snippet -->
            <div v-show="openFrames.includes(idx) && frame.snippet"
                 class="code-snippet bg-gray-900 text-gray-100 p-4 overflow-x-auto border-t dark:border-gray-700">
              <table class="w-full font-mono text-xs">
                <tbody>
                  <tr v-for="snippetLine in frame.snippet" :key="snippetLine.line"
                      :class="[snippetLine.highlighted ? 'bg-yellow-900/40' : '', 'hover:bg-gray-800']">
                    <td class="text-right pr-4 text-gray-500 select-none w-12">
                      {{ snippetLine.line }}
                    </td>
                    <td class="pl-4">
                      <pre class="m-0 whitespace-pre-wrap" :class="[snippetLine.highlighted ? 'highlighted-line' : '']"><code class="language-php">{{ snippetLine.code }}</code></pre>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Previous Exception -->
      <div v-if="exception.previous" class="previous-exception mt-6 pl-4 border-l-2 border-gray-300 dark:border-gray-700">
        <div class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">
          Previous Exception:
        </div>
        <exception-debugger :exception="exception.previous" :nested="true" />
      </div>
    </div>

    <!-- Context Exceptions -->
    <div v-if="contextExceptions && contextExceptions.length" class="context-exceptions space-y-4">
      <div class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2" v-if="!nested">
        Additional Exceptions from Context:
      </div>
      <exception-debugger v-for="(ctxException, idx) in contextExceptions"
                         :key="idx"
                         :exception="ctxException"
                         :nested="true" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import { ChevronRightIcon } from '@heroicons/vue/24/solid';
import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';

// Register PHP language
hljs.registerLanguage('php', php);

const props = defineProps({
  exception: {
    type: Object,
    default: null
  },
  contextExceptions: {
    type: Array,
    default: () => []
  },
  nested: {
    type: Boolean,
    default: false
  }
});

const showStackTrace = ref(false);
const openFrames = ref([]);

const toggleFrame = (idx) => {
  const index = openFrames.value.indexOf(idx);
  if (index > -1) {
    openFrames.value.splice(index, 1);
  } else {
    openFrames.value.push(idx);
  }
};

const highlightCode = (code) => {
  try {
    return hljs.highlight(code, { language: 'php' }).value;
  } catch (e) {
    // Fallback to plain text if highlighting fails
    return code;
  }
};

// Highlight all code blocks when component mounts or data changes
const highlightAll = () => {
  nextTick(() => {
    document.querySelectorAll('.exception-debugger pre code').forEach((block) => {
      hljs.highlightElement(block);
    });
  });
};

onMounted(() => {
  highlightAll();
});

watch([() => props.exception, () => props.contextExceptions, showStackTrace, openFrames], () => {
  highlightAll();
}, { deep: true });
</script>

<style scoped>
.exception-debugger {
  font-size: 0.875rem;
}

.code-snippet pre {
  line-height: 1.5;
}

.code-snippet pre code {
  background: transparent;
  padding: 0;
  margin: 0;
}

/* Highlighted line styling for error line */
.highlighted-line code {
  font-weight: 600;
}

.frame {
  transition: all 0.2s ease;
}
</style>

<style>
/* Import highlight.js dark theme - global to work with dynamically added elements */
@import 'highlight.js/styles/atom-one-dark.css';
</style>
