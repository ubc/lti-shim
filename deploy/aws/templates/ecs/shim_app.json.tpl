[
  {
    "name": "shim-app",
    "image": "${app_image}",
    "cpu": ${fargate_cpu},
    "memory": ${fargate_memory},
    "networkMode": "awsvpc",
    "environment": [
    	{ "name": "NODE_ENV", "value": "production" },
    	{ "name": "DB_HOST", "value": "${db_address}" },
    	{ "name": "DB_PORT", "value": "${db_port}" },
    	{ "name": "DB_USERNAME", "value": "${db_username}" },
    	{ "name": "DB_PASSWORD", "value": "${db_password}" },
    	{ "name": "ADMIN_NAME", "value": "${admin_name}" },
    	{ "name": "ADMIN_EMAIL", "value": "${admin_email}" },
    	{ "name": "ADMIN_PASSWORD", "value": "${admin_password}" }
    ],
    "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
          "awslogs-group": "/ecs/shim-app",
          "awslogs-region": "${aws_region}",
          "awslogs-stream-prefix": "ecs"
        }
    },
    "portMappings": [
      {
        "containerPort": ${app_port},
        "hostPort": ${app_port}
      }
    ]
  }
]
