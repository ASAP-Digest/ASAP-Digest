<!--
  Content Crawler Source Management Page
  -------------------------------------
  Lists all content sources, allows adding and editing sources.
  Uses crawler-api.js for backend operations.
-->
<script>
  import { onMount } from 'svelte';
  import { fetchSources, addSource, updateSource } from '$lib/api/crawler-api.js';
  import { Button, Input, Switch, Select } from '$lib/components/ui';
  import { Plus, Edit2, RefreshCw } from 'lucide-svelte';

  let sources = [];
  let isLoading = true;
  let error = '';
  let showAddForm = false;
  let showEditForm = false;
  let editSource = null;
  let form = {
    name: '',
    type: '',
    url: '',
    content_types: [],
    active: true,
    fetch_interval: 3600
  };
  let formError = '';
  let formLoading = false;

  async function loadSources() {
    isLoading = true;
    error = '';
    try {
      sources = await fetchSources();
    } catch (e) {
      error = e.message;
    } finally {
      isLoading = false;
    }
  }

  onMount(loadSources);

  function openAddForm() {
    form = { name: '', type: '', url: '', content_types: [], active: true, fetch_interval: 3600 };
    formError = '';
    showAddForm = true;
  }

  function openEditForm(source) {
    form = { ...source, content_types: Array.isArray(source.content_types) ? source.content_types : [] };
    editSource = source;
    formError = '';
    showEditForm = true;
  }

  async function handleAddSource() {
    formLoading = true;
    formError = '';
    try {
      await addSource(form);
      showAddForm = false;
      await loadSources();
    } catch (e) {
      formError = e.message;
    } finally {
      formLoading = false;
    }
  }

  async function handleEditSource() {
    formLoading = true;
    formError = '';
    try {
      await updateSource(editSource.id, form);
      showEditForm = false;
      editSource = null;
      await loadSources();
    } catch (e) {
      formError = e.message;
    } finally {
      formLoading = false;
    }
  }
</script>

<div class="p-6">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Content Sources</h1>
    <Button on:click={openAddForm}>
      <Plus class="w-4 h-4 mr-2" /> Add Source
    </Button>
  </div>

  {#if isLoading}
    <p>Loading sources...</p>
  {:else if error}
    <p class="text-red-500">{error}</p>
  {:else}
    <table class="min-w-full border text-sm">
      <thead>
        <tr>
          <th class="px-3 py-2 border">Name</th>
          <th class="px-3 py-2 border">Type</th>
          <th class="px-3 py-2 border">URL</th>
          <th class="px-3 py-2 border">Active</th>
          <th class="px-3 py-2 border">Interval (s)</th>
          <th class="px-3 py-2 border">Actions</th>
        </tr>
      </thead>
      <tbody>
        {#each sources as source}
          <tr>
            <td class="px-3 py-2 border">{source.name}</td>
            <td class="px-3 py-2 border">{source.type}</td>
            <td class="px-3 py-2 border">{source.url}</td>
            <td class="px-3 py-2 border">{source.active ? 'Yes' : 'No'}</td>
            <td class="px-3 py-2 border">{source.fetch_interval}</td>
            <td class="px-3 py-2 border">
              <Button size="sm" variant="outline" on:click={() => openEditForm(source)}>
                <Edit2 class="w-4 h-4" />
              </Button>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  {/if}

  {#if showAddForm}
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg w-[400px]">
        <h2 class="text-lg font-bold mb-4">Add Content Source</h2>
        <form on:submit|preventDefault={handleAddSource}>
          <div class="mb-2">
            <Input bind:value={form.name} placeholder="Name" required />
          </div>
          <div class="mb-2">
            <Input bind:value={form.type} placeholder="Type (rss, api, scraper)" required />
          </div>
          <div class="mb-2">
            <Input bind:value={form.url} placeholder="Source URL" required />
          </div>
          <div class="mb-2">
            <Input bind:value={form.fetch_interval} type="number" min="60" placeholder="Fetch Interval (seconds)" required />
          </div>
          <div class="mb-2 flex items-center gap-2">
            <Switch bind:checked={form.active} /> <span>Active</span>
          </div>
          {#if formError}
            <p class="text-red-500 mb-2">{formError}</p>
          {/if}
          <div class="flex justify-end gap-2 mt-4">
            <Button type="button" variant="outline" on:click={() => showAddForm = false}>Cancel</Button>
            <Button type="submit" disabled={formLoading}>{formLoading ? 'Adding...' : 'Add'}</Button>
          </div>
        </form>
      </div>
    </div>
  {/if}

  {#if showEditForm}
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg w-[400px]">
        <h2 class="text-lg font-bold mb-4">Edit Content Source</h2>
        <form on:submit|preventDefault={handleEditSource}>
          <div class="mb-2">
            <Input bind:value={form.name} placeholder="Name" required />
          </div>
          <div class="mb-2">
            <Input bind:value={form.type} placeholder="Type (rss, api, scraper)" required />
          </div>
          <div class="mb-2">
            <Input bind:value={form.url} placeholder="Source URL" required />
          </div>
          <div class="mb-2">
            <Input bind:value={form.fetch_interval} type="number" min="60" placeholder="Fetch Interval (seconds)" required />
          </div>
          <div class="mb-2 flex items-center gap-2">
            <Switch bind:checked={form.active} /> <span>Active</span>
          </div>
          {#if formError}
            <p class="text-red-500 mb-2">{formError}</p>
          {/if}
          <div class="flex justify-end gap-2 mt-4">
            <Button type="button" variant="outline" on:click={() => { showEditForm = false; editSource = null; }}>Cancel</Button>
            <Button type="submit" disabled={formLoading}>{formLoading ? 'Saving...' : 'Save'}</Button>
          </div>
        </form>
      </div>
    </div>
  {/if}
</div> 