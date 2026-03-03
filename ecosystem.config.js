module.exports = {
  apps: [
    {
      // 前端应用
      name: 'oio-frontend',
      cwd: './frontend',
      script: 'npm',
      args: 'run dev',
      env: {
        PORT: 5174
      },
      watch: false,
      ignore_watch: ['node_modules', 'dist'],
      merge_logs: true
    },
    {
      // 后端应用 (PHP)
      name: 'oio-backend',
      cwd: './backend',
      script: 'php',
      args: '-S localhost:6001',
      exec_mode: 'fork',
      merge_logs: true,
      env: {
        PHP_CLI_SERVER_WORKERS: 4
      }
    }
  ]
}
