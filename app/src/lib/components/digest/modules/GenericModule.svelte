<script>
    export let moduleData; // Data for the specific module instance
</script>

<div class="digest-module generic-module">
    <h3>{moduleData.post_title || 'Untitled Module'}</h3>
    <p>{moduleData.post_content || 'No content provided.'}</p>
    <!-- TODO: Add more sophisticated rendering based on module type if needed -->
</div>

<style>
    .generic-module {
        border: 1px solid #eee;
        padding: 15px;
        margin-bottom: 10px;
        background-color: #f9f9f9;
    }
</style> 