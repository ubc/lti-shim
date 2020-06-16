variable "aws_region" {
  description = "The AWS region things are created in"
  default     = "ca-central-1"
}

variable "ecs_task_execution_role_name" {
  description = "ECS task execution role name"
  default = "myEcsTaskExecutionRole"
}

variable "ecs_auto_scale_role_name" {
  description = "ECS auto scale role Name"
  default = "myEcsAutoScaleRole"
}

variable "az_count" {
  description = "Number of AZs to cover in a given region"
  default     = "2"
}

variable "app_image" {
  description = "Docker image to run in the ECS cluster"
  default     = "lthub/lti-shim:prototype4"
}

variable "app_port" {
  description = "Port exposed by the docker image to redirect traffic to"
  default     = 80
}

variable "app_count" {
  description = "Number of docker containers to run"
  default     = 1
}

variable "health_check_path" {
  default = "/"
}

variable "fargate_cpu" {
  description = "Fargate instance CPU units to provision (1 vCPU = 1024 CPU units)"
  default     = "1024"
}

variable "fargate_memory" {
  description = "Fargate instance memory to provision (in MiB)"
  default     = "2048"
}

variable "db_username" {
  description = "Database username"
  default     = "shim"
}

variable "db_password" {
  description = "Database password"
  default     = "password"
}

variable "db_port" {
  description = "Database port"
  default     = 5432
}

variable "admin_name" {
  description = "Administrator's name"
  default     = "admin"
}

variable "admin_email" {
  description = "Administrator's email"
  default     = "admin@example.com"
}

variable "admin_password" {
  description = "Administrator's password"
  default     = "password"
}
